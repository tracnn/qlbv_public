@extends('adminlte::page')

@section('title', 'Kiểm tra thuốc có điều kiện')

@section('content_header')
  <h1>
    Danh sách hồ sơ
    <small>Thuốc có điều kiện</small>
  </h1>
{{ Breadcrumbs::render('bhyt.index') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml-list" class="table table-hover responsive" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã LK</th>
                    <th>Họ tên</th>
                    <th>Mã thẻ</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Mã bệnh</th>
                    <th>Bệnh khác</th>
                    <th>Thuốc có điều kiện</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $key => $value)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$value->MA_LK}}</td>
                    <td>{{$value->HO_TEN}}</td>
                    <td>{{$value->MA_THE}}</td>
                    <td>{{$value->NGAY_VAO}}</td>
                    <td>{{$value->NGAY_RA}}</td>
                    <td>{{$value->MA_BENH}}</td>
                    <td>{{$value->MA_BENHKHAC}}</td>
                    <td>
                        <table class="table table-hover responsive">
                            <thead>
                                <tr>
                                    <th>Mã thuốc</th>
                                    <th>Ngày YL</th>
                                    <th>Khoa</th>
                                    <th>Giá trị ĐK</th>
                                    <th>Kiểm tra</th>
                                </tr>
                            </thead>
                        @foreach($value->xml2 as $key_xml2 => $value_xml2)
                        <tr>
                            <td>{{$value_xml2->MA_THUOC}}</td>
                            <td>{{$value_xml2->NGAY_YL}}</td>
                            <td>{{$value_xml2->MA_KHOA}}</td>
                            <td>{{$value_xml2->cat_cond_pharma->pharma_val}}</td>
                            <td>
                                @if($checked[$key][$key_xml2])
                                <span class="btn-info glyphicon glyphicon-ok">pass</span>
                                @else
                                <span class="btn-danger glyphicon glyphicon-remove">fail</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </table>
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