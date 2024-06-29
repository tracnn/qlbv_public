@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-entered'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('insurance.manager.check-entered.insurance.reports.search_checkcard')
<!-- List -->
<div class="panel panel-default">
    <div class="panel-body">
        @if($check_insurance->count())
        <!-- title -->
        <div class="row">
            <div class="col-lg-6 col-xs-6">
                <b>{{__('insurance.backend.labels.check_bussines_rules')}}</b>
            </div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <b>{{__('medreg.labels.total-records')}} {{$check_insurance->count()}}</b>
                </div>
            </div>
        </div>
        <!-- /title -->
        <div class="table table-responsive">
            <table id="print-friendly" class="table table-condensed table-hover">
            <thead>
                <th>STT</th>
                <th>Ngày khám</th>
                <th>Mã BN</th>
                <th>Tên BN</th>
                <th>Số thẻ</th>
                <th>Ngày sinh</th>
                <th>Phòng khám</th>
                <th>KQ tra cứu</th>
                <th>KQ kiểm tra</th>
                <th width="35%">Ghi chú</th>
                <th>Act</th>
                
            </thead>
            <tbody>
            @foreach($check_insurance as $key => $value)
            	<tr>
                    <td>{{$key+1}}</td>
                    <td>{{date_format(date_create($value->date_examine),'d/m/Y H:i')}}</td>    
                    <td class="col-md-1">{{$value->patient_code}}</td>
            		<td class="col-md-1">{{$value->patient_name}}</td>
            		<td class="col-md-2">{{$value->insurance_number}}</td>
            		<td>{{$value->birthday}}</td>
                    <td>{{$value->dm_phongkham->tenpk}}</td>
                    <td class="col-md-2">@if($value->result_code == '000')
                        {{$insurance_error_code[$value->result_code]}}
                        @else
                        <label style="color:red;">{{$insurance_error_code[$value->result_code]}}</label>
                        @endif
                    </td>
                    <td class="col-md-2">@if($value->check_code == '00')
                        {{$check_insurance_code[$value->check_code]}}
                        @else
                        <label style="color:red;">{{$check_insurance_code[$value->check_code]}}</label>
                        @endif
                    </td>
            		<td>{{$value->note}}</td>
                    <td>
                        <a href="{{route('insurance.check-entered.insurance.detail',['id' => $value->examine_number])}}" class="btn btn-sm btn-primary" title="{{__('insurance.backend.labels.view_detail')}}" target="_blank"><i class="fa fa-eye"></i></a>
                        <a href="{{route('insurance.check-card.search',['card-number' => $value->insurance_number, 'name' => $value->patient_name, 'birthday' => $value->birthday])}}" class="btn btn-sm btn-success" title="{{__('insurance.backend.labels.check-card')}}" target="_blank"><i class="fa fa-check"></i></a>
                    </td>
            	</tr>
            @endforeach
            </tbody>
            </table>
        </div>
        @else
            <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>
<!-- /List -->
@stop