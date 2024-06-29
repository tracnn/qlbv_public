@extends('adminlte::page')

@section('title', 'Xét nghiệm - Chẩn đoán')

@section('content_header')
  <h1>
    KHTH
    <small>Xét nghiệm - Chẩn đoán</small>
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

        <form type="GET" action="">
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
                                    <label for="khoa">Dịch vụ</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control DichvuXetnghiem" name="DichvuXetnghiem[]" multiple="">
                                        @foreach($DichvuXetnghiem as $key_DichvuXetnghiem => $value_DichvuXetnghiem)
                                        <option value="{{ $value_DichvuXetnghiem->id }}" @if(in_array($value_DichvuXetnghiem->id, $ParamDichvuXetnghiem)) selected="" @endif>{{ $value_DichvuXetnghiem->service_name }}</option>
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
                                    <label for="khoa">ICD</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control DanhmucICD" name="DanhmucICD[]" multiple="">
                                        @foreach($DanhmucICD as $key_DanhmucICD => $value_DanhmucICD)
                                        <option value="{{ $value_DanhmucICD->id }}" @if(in_array($value_DanhmucICD->id, $ParamDanhmucICD)) selected="" @endif>{{ $value_DanhmucICD->icd_code . ' - ' .$value_DanhmucICD->icd_name }}</option>
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
        <b>Thời gian xử lý cận lâm sàng</b>
    </div>
    <table id="result" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
        <thead>
            <tr>
                <th>Họ tên BN</th>
                <th>Tên dịch vụ</th>
                <th>Thời gian chỉ định</th>
                <th>Thời gian bắt đầu</th>
                <th>Thời gian kết thúc</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
</div>

@stop
@push('after-scripts')
<script>
$(document).ready(function() {
    $('.DichvuXetnghiem').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('.DanhmucICD').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });    
    $('#result').DataTable({
    });

    $('#result_th').DataTable({
    });
});

</script>
@endpush