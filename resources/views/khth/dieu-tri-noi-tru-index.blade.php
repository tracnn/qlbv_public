@extends('adminlte::page')

@section('title', 'Nhập viện nội trú')

@section('content_header')
  <h1>
    KHTH
    <small>Nhập viện nội trú</small>
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

        <form type="GET" action="{{route('khth.dieu-tri-noi-tru-search')}}">
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
                                    <label for="khoa">Phòng khám</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control exam_room" name="exam_room[]" multiple="">
                                        @foreach($exam_room as $key_exam => $value_exam)
                                        <option value="{{ $value_exam->room_id }}" @if(in_array($value_exam->room_id, $ParamExamRoom['exam_room'])) selected="" @endif>{{ $value_exam->execute_room_name }}</option>
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
                                    <label for="khoa">NVYT</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control user" name="user[]" multiple="">
                                        @foreach($user as $key_user => $value_user)
                                        <option value="{{ $value_user->loginname }}" @if(in_array($value_user->loginname, $ParamUser)) selected="" @endif>{{ $value_user->username }}</option>
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
                <th>Phòng khám</th>
                <th>Số lượng</th>
                <th>Tỷ lệ</th>
            </tr>
        </thead>
        <tbody>
        @foreach($result_th as $key => $value)
        <tr>
            <td>
                {{$value->execute_room_name}}
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
    $('.exam_room').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('.user').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });    

    $('#result_th').DataTable({
    });
});

</script>
@endpush