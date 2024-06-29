@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-entered'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<!-- List -->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div class="row">
            <div class="col-lg-6 col-xs-6"><b>{{__('insurance.backend.labels.check_bussines_rules')}}</b></div>
        </div>
        <!-- /title -->
        <div>{{__('manager.backend.labels.under_construction')}}</div>
    </div>
</div>
<!-- /List -->
@stop