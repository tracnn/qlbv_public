@extends('adminlte::page')

@section('title', 'Đổi mật khẩu')

@section('content_header')
    <h1>Xin chào bạn ! {{Auth::user()->username}}</h1>
@stop

@section('content')
@if (session('status'))
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
@endif
<div class="panel panel-default">
<div class="panel-heading">Đổi mật khẩu</div>
<div class="panel-body">
  <h3>Chức năng đang xây dựng...</h3>
</div>
</div>
@stop