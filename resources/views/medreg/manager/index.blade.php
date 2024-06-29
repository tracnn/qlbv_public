@extends('adminlte::page')

@section('title', __('medreg.labels.title'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('flash::message')
@if ($errors->any())
    <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
    </div>
@endif
<!-- /Messages -->

<!-- Search -->
@include('medreg.manager.search')
<!-- /Search -->

<!-- List -->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <h4>{{__('medreg.labels.total-records')}} {{ $MedRegs->total() }} ({{ $MedRegs->firstItem() }} -> {{ $MedRegs->lastItem() }})</h4>
        </div>
        <!-- /title -->

        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('medreg.backend.name')}}</th>
                    <th>{{__('medreg.backend.gender')}}</th>
                    <th>{{__('medreg.backend.birthday')}}</th>
                    <th>{{__('medreg.backend.email')}}</th>
                    <th>{{__('medreg.backend.phone')}}</th>
                    <th>{{__('medreg.backend.healthcaredate')}}</th>
                    <th>{{__('medreg.backend.healthcaretime')}}</th>
                    <th>Action</th>
                </tr>
                </thead>  
                    <tbody>
                        @foreach ($MedRegs as $Key => $MedReg)
                            <tr>
                                <td>{{$MedReg->name}}</td>
                                <td>{{ $gender[$MedReg->gender] }}</td>
                                <td>{{date_format(date_create($MedReg->birthday),'d/m/Y')}}</td>
                                <td>{{$MedReg->email}}</td>
                                <td>{{$MedReg->phone}}</td>
                                <td>{{date_format(date_create($MedReg->healthcaredate),'d/m/Y')}}</td>
                                <td>{{ $healthcaretime[$MedReg->healthcaretime] }}</td>
                                <td>
                                    <a href="{{route('medreg.view',['id' => $MedReg->id])}}" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                                    <a onclick="return confirm('{{ __('medreg.backend.confirm') }}');" href="{{route('medreg.delete',['id' => $MedReg->id])}}" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></a>
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
                    {{ $MedRegs->appends(Request::except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <div class="row">
            <div class="col-lg-6 col-xs-6"></div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <a href="{{route('medreg.export', $params)}}" class="btn btn-success" data-toggle="tooltip" title="Export">
                        Export<i class="fa fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /List -->

@stop
@push('after-scripts')
<script>
    $('#flash-overlay-modal').modal();
</script>
@endpush