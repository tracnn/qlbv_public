@extends('adminlte::page')

@section('title', 'Rà soát hệ thống')

@section('content_header')
{{ csrf_field() }}
@stop

@section('content')

@include('includes.message')
@include('system.check-error.search')
@include('system.check-error.result')
@stop
