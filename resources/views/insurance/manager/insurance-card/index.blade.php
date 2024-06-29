@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Danh sách
    <small>thẻ BHYT</small>
</h1>
{{ Breadcrumbs::render('insurance-card.index') }}
@stop

@section('content')

@include('includes.message')
@include('insurance.manager.insurance-card.search')
<!-- List -->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        @if($InsuranceCards->count())
        <div class="row">
            <div class="col-lg-6 col-xs-6">
            	<b>{{__('medreg.labels.total-records')}} {{ number_format($InsuranceCards->total()) }} ({{ number_format($InsuranceCards->firstItem()) }} -> {{ number_format($InsuranceCards->lastItem()) }})
            	</b>
            </div>
        </div>
        <!-- /title -->
        
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.status')}}</th>
                    <th>{{__('insurance.backend.labels.card-number')}}</th>
                    <th>{{__('insurance.backend.labels.name')}}</th>
                    <th>{{__('insurance.backend.labels.birthday')}}</th>
                    <th>{{__('insurance.backend.labels.note')}}</th>
                    <th>{{__('insurance.backend.labels.create_date')}}</th>
                    <th>Action</th>
                </tr>
                </thead>  
                <tbody>
                    @foreach ($InsuranceCards as $key => $value)
                        <tr>
                            <td class="col-md-2">
                            @if($value->maKetQua == '000')
                                <label style="color:blue;">{{$insurance_code[$value->maKetQua]}}</label>
                            @else
                                <label style="color:red;">{{$insurance_code[$value->maKetQua]}}</label>
                            @endif
                            </td>
                            <td class="col-md-1">{{ $value->maThe }}</td>
                            <td class="col-md-1">{{ $value->hoTen }}</td>
                            <td>{{ $value->ngaySinh }}</td>
                            <td class="col-md-5">{{ $value->ghiChu }}</td>
                            <td class="col-md-1">{{ \Carbon\Carbon::createFromTimestamp(strtotime($value->created_at))->diffForHumans() }}</td>
                            <td>
                                <a href="{{route('insurance-card.detail',['id' => $value->id])}}" class="btn btn-sm btn-primary" title="{{__('insurance.backend.labels.view_detail')}}"><i class="fa fa-eye"></i></a>
                                <a onclick="return confirm('{{ __('medreg.backend.confirm') }}');" href="{{route('insurance-card.delete',['id' => $value->id])}}" class="btn btn-sm btn-danger" title="{{__('insurance.backend.labels.delete')}}"><i class="fa fa-trash"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>              
            </table>
        </div>

        <div>
            {{ $InsuranceCards->appends(Request::except('page'))->links() }}
        </div>
        @else
        	<center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>
<!-- /List -->

@stop