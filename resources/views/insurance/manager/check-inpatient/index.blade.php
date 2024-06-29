@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-entered'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('insurance.manager.check-inpatient.search')
@include('insurance.manager.check-inpatient.result')
@stop
