@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-entered'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<!-- Search -->
@include('insurance.manager.check-entered.insurance.search')
<!-- /Search -->

<!-- List -->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        @if($models->count())
        <div class="row">
            <div class="col-lg-6 col-xs-6"><b>{{__('medreg.labels.total-records')}} {{ number_format($models->total()) }} ({{ number_format($models->firstItem()) }} -> {{ number_format($models->lastItem()) }})</b></div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <div pull-right><a onclick="return confirm('{{ __('insurance.backend.confirm') }}');" class="btn btn-sm btn-primary" href="{{route('insurance.check-entered.insurance.check-bussines-rules',Request::all())}}">{{__('insurance.backend.labels.check_bussines_rules')}}</a></div>
                </div>
            </div>
        </div>
        <!-- /title -->
        
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.id_number')}}</th>
                    <th>{{__('insurance.backend.labels.card-number')}}</th>
                    <th>{{__('insurance.backend.labels.date_checkup')}}</th>
                    <th>Mã BN</th>
                    <th>{{__('insurance.backend.labels.name')}}</th>
                    <th>{{__('insurance.backend.labels.birthday')}}</th>
                    <th>Số tiền</th>
                    <th>Action</th>
                </tr>
                </thead>  
                    <tbody>
                        @foreach ($models as $key => $value)
                            <tr>
                                <td>{{ $value->sophieu }}</td>
                                <td>{{ $value->sothe }}</td>
                                <td>{{ date_format(date_create($value->ngaykham),'d/m/Y H:m') }}</td>
                                <td>{{$value->mabenhnhan}}</td>
                                <td>{{ $value->hotenbn }}</td>
                                <td>{{ date_format(date_create(substr($value->bn_hc->ngaysinh,0,10)),'d/m/Y') }}</td>
                                <td class="text-right">{{number_format($value->tongcong)}}</td>
                                <td>
                                	<a href="{{route('insurance.check-entered.insurance.detail',['id' => $value->sophieu])}}" class="btn btn-sm btn-primary" title="{{__('insurance.backend.labels.view_detail')}}" target="_blank"><i class="fa fa-eye"></i></a>
                                    <a href="{{route('insurance.check-card.search',['card-number' => $value->sothe, 'name' => $value->hotenbn, 'birthday' => date_format(date_create(substr($value->bn_hc->ngaysinh,0,10)),'d/m/Y')])}}" class="btn btn-sm btn-success" title="{{__('insurance.backend.labels.check-card')}}" target="_blank"><i class="fa fa-check"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>              
            </table>
        </div>

        <div>
            {{ $models->appends(Request::except('page'))->links() }}
        </div>
        @else
        	<center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>
<!-- /List -->

@stop