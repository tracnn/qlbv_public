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
        <table id="emr-treatment" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
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

@if(isset($sere_serv_cdha) && count($sere_serv_cdha))
<div class="panel panel-default">
    <div class="panel-heading">
        CĐHA
    </div>
    <div class="panel-body table-responsive">
        <table id="service_cdha" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
          <thead>
            <tr>
              <th>STT</th>
              <th>Tên dịch vụ</th>
              <th>Tác vụ</th>
            </tr>
          </thead>
          <tbody>
            @foreach($sere_serv_cdha as $key => $value)
            <tr>
              <td align="center">
                {{ $key + 1 }}
              </td>
              <td>
                {{ $value->tdl_service_name }}
              </td>
              <td align="center">
                <a href="{{ config('organization.base_pacs_url') }}{{ $value->id }}{{ config('organization.pacs_url_suffix') ? config('organization.pacs_url_suffix') . $value->id : '' }}" 
                class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer">
                  <i class="fa fa-film"></i> Xem
                </a>
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
        <div class="pull-right">
            <label style="font-weight: normal; margin-bottom: 0;">
                <input type="checkbox" id="groupByDocumentType" style="margin-right: 5px;">
                Nhóm theo Loại văn bản
            </label>
        </div>
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
                <th>Tác vụ</th>
            </tr>
        </thead>
        <tbody>
        @foreach($emr_document as $key => $value)
        <tr data-document-type="{{$value->document_type_name}}">
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
                @php
                    $createdAt = now()->timestamp;
                    $expiresIn = 7200;
                    $token = Crypt::encryptString($value->document_code . '|' . $value->treatment_code . '|' . $createdAt . '|' . $expiresIn);
                @endphp
                <a href="{{route('secure-view-doc',['token' => $token])}}" class="btn btn-sm btn-primary" target="_blank">
                    <span class="glyphicon glyphicon-eye-open"></span> Xem PDF</a>
                <button class="share-modal btn btn-sm btn-info" data-id="{{($value->document_code)}}" 
                    data-title="{{($value->document_name)}}" data-content="">
                    <span class="glyphicon glyphicon-qrcode"></span> Share
                </button>
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
var emrDocumentTable;
var originalDocumentData = [];

$(document).ready(function() {
    $('#emr-treatment').DataTable({
    });
    
    // Lưu dữ liệu gốc từ các row trước khi khởi tạo DataTable
    $('#emr-document tbody tr').each(function() {
        originalDocumentData.push($(this).clone(true, true));
    });
    
    // Khởi tạo DataTable
    emrDocumentTable = $('#emr-document').DataTable({
    });
    
    $('#service_cdha').DataTable({
    });
});

// Xử lý nhóm theo Loại văn bản
$(document).on('change', '#groupByDocumentType', function() {
    var isGrouped = $(this).is(':checked');
    var tbody = $('#emr-document tbody');
    var table = $('#emr-document');
    
    // Phá hủy DataTable hiện tại
    if (emrDocumentTable) {
        emrDocumentTable.destroy();
        emrDocumentTable = null;
    }
    
    // Xóa nội dung tbody
    tbody.empty();
    
    if (isGrouped) {
        // Nhóm dữ liệu theo Loại văn bản
        var groupedData = {};
        var stt = 1;
        
        originalDocumentData.forEach(function(row) {
            var documentType = row.attr('data-document-type');
            if (!groupedData[documentType]) {
                groupedData[documentType] = [];
            }
            groupedData[documentType].push(row);
        });
        
        // Hiển thị dữ liệu đã nhóm
        Object.keys(groupedData).sort().forEach(function(documentType) {
            // Thêm header cho nhóm với đủ 5 cột
            var groupRow = $('<tr class="group-header" style="background-color: #f5f5f5; font-weight: bold;">');
            groupRow.append('<td colspan="5" style="padding: 10px 15px;">');
            groupRow.find('td').html('<span class="glyphicon glyphicon-folder-open" style="margin-right: 5px;"></span>' + 
                documentType + ' <span class="badge">' + groupedData[documentType].length + '</span>');
            tbody.append(groupRow);
            
            // Thêm các row trong nhóm
            groupedData[documentType].forEach(function(row) {
                var newRow = row.clone(true);
                newRow.find('td:first').text(stt);
                tbody.append(newRow);
                stt++;
            });
        });
        
        // Không khởi tạo DataTable khi ở chế độ nhóm (vì có colspan)
        // Chỉ áp dụng style cho bảng
        table.addClass('table-hover');
    } else {
        // Hiển thị dữ liệu gốc
        originalDocumentData.forEach(function(row, index) {
            var newRow = row.clone(true);
            newRow.find('td:first').text(index + 1);
            tbody.append(newRow);
        });
        
        // Khởi tạo lại DataTable khi không nhóm
        emrDocumentTable = $('#emr-document').DataTable({
            "order": []
        });
    }
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