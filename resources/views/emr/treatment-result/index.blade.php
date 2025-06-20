@extends('adminlte::page')

@section('title', 'Trả kết quả cho BN')

@section('content_header')
<h1>
    Trả kết quả
    <small>cho bệnh nhân</small>
</h1>
{{ Breadcrumbs::render('treatment-result.index') }}
@stop

@section('content')

@include('includes.message')
@include('emr.treatment-result.search')

@php
    use Illuminate\Support\Facades\Crypt;
@endphp

@if(isset($patient_info) && $patient_info)
<div class="panel panel-default">
    <div class="panel-heading">
        Thông tin hành chính
    </div>
    <div class="panel-body table-responsive">
        <table id="emr-treatment" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th>Mã BN</th>
                    <th>Tên BN</th>
                    <th>Năm sinh</th>
                    <th>Số điện thoại</th>
                    <th>Thẻ BHYT</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Đối tượng</th>
                    <th>Tác vụ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patient_info as $key => $value)
                <tr>
                    <td>{{$value->treatment_code}}</td>
                    <td>{{$value->patient_code}}</td>
                    <td>{{$value->vir_patient_name}}</td>
                    <td>{{strtodate($value->dob)}}</td>
                    <td id="phone">{{$value->mobile}}{{$value->phone}} <a href="#" class="editPhone" 
                        data-id="{{$value->patient_code}}" data-phone="{{$value->mobile}}{{$value->phone}}">
                        <span class="glyphicon glyphicon-edit"></span></a>
                    </td>
                    <td>{{$value->tdl_hein_card_number}}</td>
                    <td>{{strtodatetime($value->in_time)}}</td>
                    <td>{{$value->out_time ? strtodatetime($value->out_time) : ''}}</td>
                    <td>{{$value->patient_type_name}}</td>
                    @php
                        $createdAt = now()->timestamp;
                        $expiresIn = 7200;
                        $token = Crypt::encryptString($value->treatment_code . '|' . $value->phone . '|' . $createdAt . '|' . $expiresIn);
                    @endphp
                    <td><a href="{{route('view-guide-content',['token' => $token])}}" class="btn btn-sm btn-primary" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> Hướng dẫn - Xem KQ</a>
                        <a href="{{route('view-mety',['treatment_id' => $value->id])}}" class="btn btn-sm btn-info" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> Đơn thuốc</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="panel panel-default">
	<div class="panel-heading">
        Phiếu trả kết quả
    </div>
<div class="panel-body table-responsive">
@if(isset($emr_document) && $emr_document)
    <table id="emr-document" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên văn bản</th>
                <th>Loại văn bản</th>
                <th>Ngày tạo</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        @foreach($emr_document as $key => $value)
        <tr>
            <td>
                {{$key + 1}}
            </td>
            <td>
                {{$value->document_name}}
            </td>
            <td>
                {{$value->document_type_name}}
            </td>
            <td>
            	{{strtodate($value->create_date)}}
            </td>
            <td>
                <a href="{{route('view-doc',['document_code' => ($value->document_code),
                    'treatment_code' => $value->treatment_code])}}" class="btn btn-sm btn-primary" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> Xem</a>
                <button class="share-modal btn btn-sm btn-info" data-id="{{($value->document_code)}}" data-title="{{($value->document_name)}}" data-content=""><span class="glyphicon glyphicon-qrcode"></span> Share</button>
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

<!-- Modal form to share doc-->
<div id="shareModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group" align="center">
                        <label id="title"></label></br>
                        <img id="img-src" style="max-height: 300px; max-width: 300px;">
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-dismiss="modal">
                        <span class='glyphicon glyphicon-remove'></span> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to share doc-->

<!-- Modal form to edit Phone number -->
<div id="editModalPhone" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="dynamic">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="patientCode" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Nhập số điện thoại:</label>
                        <div class="col-sm-9">
                            <input type="tel" class="form-control" id="phoneNumber">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>      
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="updatePhone btn btn-primary edit" data-dismiss="modal" accesskey="s">
                        <span class='glyphicon glyphicon-check'></span> Lưu (Alt+S)
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal" accesskey="c">
                        <span class='glyphicon glyphicon-remove'></span> Đóng (Alt+C)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to edit Phone number -->

@stop

@push('after-scripts')
<script>
$(document).ready(function() {
    $('#emr-treatment').DataTable({
    });
    $('#emr-document').DataTable({
    });
});

$(document).on('click', '.share-modal', function() {
    $("#img-src").attr("src", "");
    $.ajax({
        url: "{{route('treatment-result.qr-code')}}",
        type: "GET",
        data: {
            _token: "{{csrf_token()}}",
            document_code: $(this).data('id'),
        },
    })
    .done(function(data) {
        $("#img-src").attr("src", data);
    })
    $('#title').html($(this).data('title'));
    $('#shareModal').modal('show');
});

$(document).on('click', '.editPhone', function() {
    $('#patientCode').val($(this).data('id'));
    $('#phoneNumber').val($(this).data('phone'));
    $('#editModalPhone').modal('show');
});

$(document).on('click', '.updatePhone', function() {
    $.ajax({
        url: "{{route('treatment-result.update-phone')}}",
        type: "POST",
        data: {
            _token: "{{csrf_token()}}",
            patientCode: $('#patientCode').val(),
            phoneNumber: $('#phoneNumber').val(),
        },
    })
    .done(function(data) {
        if (data.maKetqua == '200') {
            toastr.success(data.noiDung);
            window.location.reload();
        } else {
            toastr.error(data.noiDung);
            window.location.reload();
        }
    })
});

</script>
@endpush