@extends('adminlte::page')

@section('title', 'Dashboard lãnh đạo')

@section('content_header')
    <h1>Dashboard lãnh đạo</h1>
@stop

@section('content')
    <div id="lanh-dao-app"></div>
@stop

@section('js')
    <script>window.LANH_DAO_CFG = { url: '{{ url('dashboard/lanh-dao') }}' };</script>
@stop
