@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-card'))

@section('content_header')
<h1>
    Tra cứu
    <small>thẻ BHYT</small>
</h1>
<ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Dashboard</li>
</ol>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<!-- Search -->
@include('insurance.manager.check-card.search')
<!-- /Search -->

<!-- Result -->
@include('insurance.manager.check-card.result')
<!-- /Result -->
@stop