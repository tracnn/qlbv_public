@extends('adminlte::page')

@section('title', 'Nội trú theo khoa')

@section('content_header')
  <h1>
    KHTH
    <small>Nội trú theo khoa</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Tìm kiếm</b>
        </div>

        <form type="GET" action="{{route('khth.noi-tru-theo-khoa-search')}}">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="ngay_ttoan">Từ</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" name="tu_ngay" value="{{$ParamNgay['tu_ngay']}}">
                                    </div>
                                </div> 
                            </div>
                           
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="ngay_ttoan">Đến</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" name="den_ngay" value="{{$ParamNgay['den_ngay']}}">
                                    </div>
                                </div>   
                            </div>
                         
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="khoa">Khoa điều trị</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control department" name="department[]" multiple="">
                                        @foreach($department as $key_department => $value_department)
                                        <option value="{{ $value_department->id }}" @if(in_array($value_department->id, $ParamDepartment)) selected="" @endif>{{ $value_department->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>                        
                            </div>

                        </div>
                    </div>
                </div>                    
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="khoa">Đối tượng</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control patient_type" name="patient_type[]" multiple="">
                                        @foreach($patient_type as $key_patient_type => $value_patient_type)
                                        <option value="{{ $value_patient_type->id }}" @if(in_array($value_patient_type->id, $ParamPatientType)) selected="" @endif>{{ $value_patient_type->patient_type_name }}</option>
                                        @endforeach
                                    </select>
                                </div>                        
                            </div>

                        </div>                    
                    </div>
                </div>                    
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    Tìm kiếm
                </button>
            </div>              
        </form>
    </div>
</div>

<div class="panel panel-default">
<div class="panel-body table-responsive">
    <div class="form-group">
        <b>Tổng hợp: 
        @if(isset($result_th) && $result_th->count())
            <strong style="color:Tomato;">{{number_format($result_th->sum('so_luong'))}}</strong>
        @endif
        </b>
    </div>
@if(isset($result_th) && $result_th->count())
    <table id="result_th" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
        <thead>
            <tr>
                <th>Khoa điều trị</th>
                <th>Số lượng</th>
                <th>Tỷ lệ</th>
            </tr>
        </thead>
        <tbody>
        @foreach($result_th as $key => $value)
        <tr>
            <td>
                {{$value->department_name}}
            </td>
            <td align="right">
                {{number_format($value->so_luong)}}
            </td>
            <td align="right">
                {{$result_th->sum('so_luong') ? number_format($value->so_luong * 100 / $result_th->sum('so_luong'),2) : ''}}%
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
@else
<center>{{__('insurance.backend.labels.no_information')}}</center>
@endif
</div>
</div>

@stop
@push('after-scripts')
<script>
$(document).ready(function() {
    $('.department').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('.patient_type').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('#result_th').DataTable({
    });

});

</script>
@endpush