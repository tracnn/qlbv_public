@extends('adminlte::page')

@section('title', 'Công văn 19031-BHXH')

@section('content_header')
  <h1>
    KHTH
    <small>Công văn 19031-BHXH</small>
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

        <form type="GET" action="{{route('khth.cong-van-19031-search')}}">
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
        <p>
            <b>1. Tổng số BN nhập viện theo ngày: 
                <strong style="color:Tomato;">{{$result_bn_nhap_vien ? $result_bn_nhap_vien : '0'}}</strong>
            </b>
        </p>
        <p>
            <b>&emsp;Tổng số BN nội trú ra viện theo ngày: 
                <strong style="color:Tomato;">{{$result_bn_ra_vien ? $result_bn_ra_vien : '0'}}</strong>
            </b>
        </p>
        <p>
            <b>&emsp;Tổng số BN nội trú chuyển viện theo ngày: 
                <strong style="color:Tomato;">{{$result_bn_chuyen_vien ? $result_bn_chuyen_vien : '0'}}</strong>
            </b>
        </p>
        <p>
            <b>&emsp;Tổng số BN nội trú tử vong theo ngày: 
                <strong style="color:Tomato;">{{$result_bn_tu_vong ? $result_bn_tu_vong : '0'}}</strong>
            </b>
        </p>
        <p>
            <b>2. Tổng số BN khám bệnh, ngoại trú: 
                <strong style="color:Tomato;">{{$result_bn_ngoai_tru ? $result_bn_ngoai_tru : '0'}}</strong>
            </b>
        </p>
    </div>
</div>
</div>

@stop
@push('after-scripts')
<script>
$(document).ready(function() {
    
});

</script>
@endpush