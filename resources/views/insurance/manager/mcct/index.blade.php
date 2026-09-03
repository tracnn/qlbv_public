@extends('adminlte::page')

@section('title', 'Tra cứu tiền cùng chi trả')

@section('content_header')
<h1>
    Tra cứu
    <small>tiền cùng chi trả / miễn cùng chi trả</small>
</h1>
@stop

@section('content')
@include('includes.message')
@include('insurance.manager.mcct.search')
@include('insurance.manager.mcct.result')
@stop
