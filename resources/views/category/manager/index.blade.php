@extends('adminlte::page')

@section('title', __('manager.backend.labels.title'))

@section('content_header')
@stop

@section('content')
<!-- Search -->
@include('category.manager.search')
<!-- /Search -->

<!-- List -->
<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-lg-6 col-xs-6"><h4>{{__('medreg.labels.total-records')}} {{ $model->total() }} ({{ $model->firstItem() }} -> {{ $model->lastItem() }})</h4></div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <a href="#" class="btn btn-success add-modal" data-toggle="tooltip" title="Thêm mới">
                        <i class="fa fa-plus-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{ __('medreg.backend.category.code') }}</th>
                    <th>{{ __('medreg.backend.category.name') }}</th>
                    <th>{{ __('medreg.backend.category.created') }}</th>
                    <th>Action</th>
                </tr>
                </thead>  
                    <tbody>
                        @foreach ($model as $key => $value)
                            <tr>
                                <td>{{ $value->code }}</td>
                                <td>{{ $value->name }}</td>
                                <td>{{date_format(date_create($value->create_at),'d/m/Y')}}</td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-primary view-modal"><i class="fa fa-eye"></i></a>
                                    <a href="#" class="btn btn-sm btn-warning edit-modal"><i class="fa fa-pencil"></i></a>
                                    <a onclick="return confirm('{{ __('medreg.backend.confirm') }}');" href="#" class="btn btn-sm btn-danger delete-modal"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>              
            </table>
        </div>
        <div class="row">
            <div class="col-lg-6 col-xs-6">
                
            </div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    {{ $model->appends(Request::except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /List -->

@stop