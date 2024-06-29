@extends('adminlte::page')

@section('title', 'Chi tiết hóa đơn')

@section('content_header')

@stop

@section('content')

@include('includes.message')

<div class="panel panel-default">
<div class="panel-body table-responsive">
@if(isset($hoadonrvchitiet) && $hoadonrvchitiet->count())
<!-- title -->
    <div>
        <b>Viện phí</b>
    </div>
    <!-- /title -->

    <div class="table table-responsive">
        <table class="table table-condensed table-hover">
            <thead>
                <tr>
                    <th>Mã lần khám</th>
                    <th>Ngày thu</th>
                    <th>Mã biên lai</th>
                    <th>Quyển thu</th>
                    <th>Số hoá đơn</th>
                    <th>Tổng tiền</th>
                    <th>Hoàn trả/Thu thêm</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>  
            <tbody>
                <tr>
                    <td>{{$hoadonravien->malankham}}</td>
                    <td>{{date_format(date_create($hoadonravien->ngaythu),'d/m/Y H:i')}}</td>
                    <td>{{$hoadonravien->idhoadon}}</td>
                    <td>{{$hoadonravien->bn_vpsohd->tenso}}</td>
                    <td>{{$hoadonravien->sobienlai}}</td>
                    <td class="text-right">{{number_format($hoadonravien->tongtien)}}</td>
                    <td class="text-right">{{number_format($hoadonravien->hoantra)}}/{{number_format($hoadonravien->thuthem)}}</td>
                    <td>{{$huybl[$hoadonravien->huybl]}}</td>
                </tr>
            </tbody>             
        </table>
    </div>
    <div>
        <b>Chi tiết dịch vụ</b>
    </div>
    <div class="table table-responsive">
        <table class="table table-condensed table-hover">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã DV</th>
                    <th>Tên DV</th>
                    <th>Ngày chỉ định</th>
                    <th>Đối tượng</th>
                    <th>Ghi chú</th>
                    <th>Số lượng</th>
                    <th>Giá tiền</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
            @foreach($hoadonrvchitiet as $key_vpntct =>$value_vpntct)
            <tr>
                <td class="text-right">{{$key_vpntct+1}}.</td>
                <td>{{$value_vpntct->madv}}</td>
                <td>{{$value_vpntct->tendichvu}}</td>
                <td>
                    @if($value_vpntct->ngaycd > $value_vpntct->hoadonravien->bn_xuatvien->ngay || $value_vpntct->ngaycd < $value_vpntct->hoadonravien->bn_nhapvien->ngay)
                    <div class="bg-danger">{{date_format(date_create($value_vpntct->ngaycd),'d/m/Y H:i')}}</div>
                    @else
                    {{date_format(date_create($value_vpntct->ngaycd),'d/m/Y H:i')}}
                    @endif
                    
                </td>
                <td>
                @if($value_vpntct->madoituong == 2)
                    <label class="label label-danger">{{$duoc_doituong[$value_vpntct->madoituong]}}</label>
                @else
                    <label class="label label-primary">{{$duoc_doituong[$value_vpntct->madoituong]}}</label>
                @endif                               
                </td>
                <td>
                    {{$value_vpntct->dm_dichvudtnt ? $value_vpntct->dm_dichvudtnt->ma_tuong_duong : ''}}
                    {{$value_vpntct->dc_dm_thuocvt ? $duoc_act[$value_vpntct->dc_dm_thuocvt->act] : ''}}
                    {{$value_vpntct->dm_xetnghiembv ? $value_vpntct->dm_xetnghiembv->ma_tuong_duong : ''}}
                </td>
                <td class="text-right">{{number_format($value_vpntct->soluong,1)}}</td>
                <td class="text-right">{{number_format($value_vpntct->dongia)}}</td>
                <td class="text-right">{{number_format($value_vpntct->dongia*$value_vpntct->soluong)}}</td>
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
@stop