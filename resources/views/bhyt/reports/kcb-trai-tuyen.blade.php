@extends('adminlte::page')

@section('title', 'Kiểm tra khám chữa bệnh trái tuyến')

@section('content_header')
  <h1>
    Danh sách
    <small>hồ sơ KCB trái tuyến</small>
  </h1>
{{ Breadcrumbs::render('bhyt.index') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml-list" class="table table-hover responsive nowrap" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã điều trị</th>
                    <th>Họ tên</th>
                    <th>Mã thẻ</th>
                    <th>Nơi ĐKBĐ</th>
                    <th>Nơi CV</th>
                    <th>Lý do VV</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Loại KCB</th>
                    <th>Kết quả</th>
                    <th>Tình trạng</th>
                    <th>Khoa</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($xml1 as $key => $value)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$value->MA_LK}}</td>
                    <td>{{$value->HO_TEN}}</td>
                    <td>{{$value->MA_THE}}</td>
                    <td>{{$value->MA_DKBD}}</td>
                    <td>{{$value->MA_NOI_CHUYEN}}</td>
                    <td>{{config('__tech.ly_do_vvien')[$value->MA_LYDO_VVIEN]}}</td>
                    <td>{{$value->NGAY_VAO}}</td>
                    <td>{{$value->NGAY_RA}}</td>
                    <td>{{config('__tech.loai_kcb')[$value->MA_LOAI_KCB]}}</td>
                    <td>{{config('__tech.ket_qua_dtri')[$value->KET_QUA_DTRI]}}</td>
                    <td>{{config('__tech.tinh_trang_rv')[$value->TINH_TRANG_RV]}}</td>
                    <td>{{$value->department ? $value->department->TEN_KHOA : ''}}</td>
                    <td>
                        <a href="{{route('bhyt.detailxml',['ma_lk'=>$value->MA_LK])}}" class="btn btn-sm btn-info" target="_blank">
                            <span class="glyphicon glyphicon-eye-open"></span> Xem hồ sơ</a>
                        <a href="{{route('insurance.check-card.search',['card-number' => $value->MA_THE, 'name' => $value->HO_TEN, 'birthday' => date_format(date_create(substr($value->NGAY_SINH,0,8)),'d/m/Y')])}}" class="btn btn-sm btn-success" title="{{__('insurance.backend.labels.check-card')}}" target="_blank"><span class="glyphicon glyphicon-check"></span> Tra cứu thẻ</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#xml-list').DataTable();
});
</script>
@endpush