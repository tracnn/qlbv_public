@extends('adminlte::page')

@section('title', $baohiem_tong->hotenbn ? $baohiem_tong->hotenbn : '')

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

@include('insurance.manager.check-entered.insurance.includes.detail_baohiem_tong')

@include('insurance.manager.check-entered.insurance.includes.detail_baohiem_congkham')

@include('insurance.manager.check-entered.insurance.includes.detail_baohiem_thuoc')

@include('insurance.manager.check-entered.insurance.includes.detail_baohiem_cls')

@include('insurance.manager.check-entered.insurance.includes.detail_baohiem_dichvukt')

@stop