@extends('adminlte::page')

@section('title', 'Kiểm tra hồ sơ bệnh án')

@section('content_header')
<h1>
    Bệnh án điện tử
    <small>Tra soát hồ sơ bệnh án</small>
</h1>
{{ Breadcrumbs::render('emr.index') }}
@stop

@section('content')
@include('includes.message')

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
                    <th>Mã thẻ</th>
                    <th>Đối tượng</th>
                    <th>Mã ICD</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Kết quả</th>
                    <th>Loại ra viện</th>
                    <th>Khoa kết thúc</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($emr_treatment as $key => $value)
                <tr>
                    <td>{{$value->treatment_code}}</td>
                    <td>{{$value->patient_code}}</td>
                    <td>{{$value->vir_patient_name}}</td>
                    <td>{{strtodate($value->dob)}}</td>
                    <td>{{$value->hein_card_number}}</td>
                    <td>{{$value->patient_type_name}}</td>
                    <td>{{$value->icd_code}}</td>
                    <td>{{strtodatetime($value->in_time)}}</td>
                    <td>{{$value->out_time ? strtodatetime($value->out_time) : ''}}</td>
                    <td>{{$value->treatment_result_name}}</td>
                    <td>{{$value->treatment_end_type_name}}</td>
                    <td>{{$value->current_department_name}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <b>Văn bản chưa tạo lập</b>
    </div>
    <div class="panel-body table-responsive">
        <table id="emr-ko-tao" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã văn bản</th>
                    <th>Tên văn bản</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($emr_document_ko_tao))
                    @foreach ($emr_document_ko_tao as $key => $value)
                    <tr @if(in_array($value->document_type_code, $ma_van_ban_qt)) style="background-color:#FF0000" @endif>
                        <td>{{$key + 1}}</td>
                        <td>{{$value->document_type_code}}</td>
                        <td>{{$value->document_type_name}}</td>
                        <td></td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <b>EMR chưa hoàn thiện (<span style="color: red">Chưa đủ người ký: {{number_format($emr_document_uncomp->count())}})</span></b>
    </div>
    <div class="panel-body table-responsive">
        <table id="emr-un-comp" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Tên văn bản</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th>Từ chối ký</th>
                    <th>Ký tiếp theo</th>
                    <th>Chưa ký</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($emr_document_uncomp))
                    @foreach ($emr_document_uncomp as $key => $value)
                    <tr>
                        <td>{{$key + 1}}</td>
                        <td>{{$value->document_type_name}}</td>
                        <td>{{$value->request_loginname}}</td>
                        <td>{{strtodate($value->document_date ? $value->document_date : $value->create_date)}}</td>
                        <td>{{$value->rejecter}}</td>
                        <td>{{$value->next_signer}}</td>
                        <td>{{$value->un_signers}}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>


<div class="panel panel-default">
    <div class="panel-heading">
        <b>Tờ điều trị (<span style="color: red">Chưa ký: {{number_format($count_tracking_nosign)}}</span>)</b>
    </div>
    <div class="panel-body table-responsive">
        <table id="emr-check" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($trackings))
                    @foreach ($trackings as $key => $value)
                    <tr @if(!$value['signed']) style="background-color:#FF0000" @endif>
                        <td>{{$key + 1}}</td>
                        <td>{{$value['creator']}}</td>
                        <td>{{strtodatetime($value['tracking_time'])}}</td>
                        <td>{{$value['signed'] ? 'Đã ký' : 'Chưa ký'}}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script>
$(document).ready(function() {
    $('#emr-ko-tao').DataTable({
    });
    $('#emr-un-comp').DataTable({
    });
    $('#emr-check').DataTable({
    });
    $('#emr-treatment').DataTable({
    });
});

</script>
@endpush