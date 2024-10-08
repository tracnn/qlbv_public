@extends('adminlte::page')

@section('title', 'Dịch vụ kỹ thuật')

@section('content_header')
  <h1>
    KHTH
    <small>Dịch vụ kỹ thuât</small>
  </h1>

@push('after-styles')
<style type="text/css">
    .select2-selection--single {
        height: 100% !important;
    }
    .select2-selection__rendered{
        word-wrap: break-word !important;
        text-overflow: inherit !important;
        white-space: normal !important;
    }
</style>
@endpush

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Điều kiện lọc</b>
        </div>

        <form type="GET" id="myform" action="{{route('khth.dich-vu-ky-thuat-search')}}">
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
                                        <input class="form-control" type="date" id="tu_ngay" name="tu_ngay" value="{{$ParamNgay['tu_ngay']}}">
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
                                        <input class="form-control" type="date" id="den_ngay" name="den_ngay" value="{{$ParamNgay['den_ngay']}}">
                                    </div>
                                </div>   
                            </div>
                         
                        </div>

                    </div>
                </div>
            </div>
        <div class="collapse multi-collapse" id="advance_search">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label for="khoa">Loại DVKT</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control loai_dvkt" id="loai_dvkt" name="loai_dvkt[]" multiple="">
                                                @foreach($LoaiDVKT as $key => $value)
                                                <option value="{{ $value->id }}" @if(in_array($value->id, $ParamLoaiDVKT)) selected="" @endif>{{ $value->service_type_name }}</option>
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
                                            <label for="khoa">DVKT</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control dvkt" id="dvkt" name="dvkt[]" multiple="">
                                                @foreach($ParamDvkt as $key => $value)
                                                <option value="{{ $value->service_code }}" selected="">{{$value->service_name}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>

                                </div>
                            </div>
                        </div>                    
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label for="khoa">Khoa chỉ định</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control department" id="department" name="department[]" multiple="">
                                                @foreach($Department as $key => $value)
                                                <option value="{{ $value->id }}" @if(in_array($value->id, $ParamDepartment)) selected="" @endif>{{ $value->department_name }}</option>
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
                                            <label for="khoa">Khoa thực hiện</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control execute_department" id="execute_department" name="execute_department[]" multiple="">
                                                @foreach($Department as $key => $value)
                                                <option value="{{ $value->id }}" @if(in_array($value->id, $ParamExecuteDepartment)) selected="" @endif>{{ $value->department_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>

                                </div>
                            </div>
                        </div>                    
                    </div>

                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group row">

                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label for="khoa">Phòng chỉ định</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control request_room" id="request_room" name="request_room[]" multiple="">
                                                @foreach($Room as $key => $value)
                                                <option value="{{ $value->id }}" @if(in_array($value->id, $ParamRequestRoom)) selected="" @endif>{{ $value->name }}</option>
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
                                            <label for="khoa">Phòng thực hiện</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control execute_room" id="execute_room" name="execute_room[]" multiple="">
                                                @foreach($ExecuteRoom as $key => $value)
                                                <option value="{{ $value->room_id }}" @if(in_array($value->room_id, $ParamExecuteRoom)) selected="" @endif>{{ $value->execute_room_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>

                                </div>
                            </div>
                        </div>                    
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12">
                                <div class="form-group row">
                                    <div class="col-sm-12 row">
                                        <div class="col-sm-3">
                                            <label for="khoa">ICD</label>
                                        </div>
                                        <div class="col-sm-9 select2">
                                            <select class="form-control icd" id="icd" name="icd[]" multiple="">
                                                @foreach($ParamIcd as $key => $value)
                                                <option value="{{ $value->icd_code }}" selected="">{{$value->icd_name}}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>                        
                                    </div>

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
                    Thống kê
                </button>
                <a class="btn btn-primary" data-toggle="collapse" href="#advance_search" role="button" aria-expanded="false" aria-controls="advance_search">Nâng cao</a>
                <a id="download" class="btn btn-success" href="javascript:" role="button"><i class="glyphicon glyphicon-download-alt"></i> Tải về XLS</a>
            </div>  

        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách DVKT
    </div>
    <div class="panel-body table-responsive">
        <table id="dvkt-index" class="table display table-hover responsive datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th>Tên BN</th>
                    <th>Năm sinh</th>
                    <th>Ngày chỉ định</th>
                    <th>Mã thẻ</th>
                    <th>Tên dịch vụ</th>
                    <th>Phòng thực hiện</th>
                    <th>BS chỉ định</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal form to export_xls -->
<div id="export_xls" class="modal fade" role="dialog" data-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <div class="col-sm-6">
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="1"> Nhóm theo dịch vụ kỹ thuật
                                </label>
                            </div>
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="2"> Nhóm theo mã điều trị
                                </label>
                            </div>
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="3"> Nhóm theo khoa chỉ định
                                </label>
                            </div>
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="4"> Nhóm theo khoa thực hiện
                                </label>
                            </div>
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="5"> Nhóm theo phòng chỉ định
                                </label>
                            </div>
                            <div>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="group_by" value="6"> Nhóm theo phòng thực hiện
                                </label>
                            </div>                        
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button id="action" type="button" class="btn btn-info">
                        <span class='glyphicon glyphicon-check'></span> Đồng ý
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal">
                        <span class='glyphicon glyphicon-remove'></span> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to export_xls -->

@stop

@push('after-scripts')
<script>

$('group_by').click(function(e){
    if (e.ctrlKey || e.metaKey) {
        $(this).prop('checked', false);
    }
});

$(document).on('click', '#download', function(){
    $('.modal-title').html('Nâng cao');
    $('#export_xls').modal('show');
})

$(document).on('click', '#action', function(){
    var group_by = [];
    $("input:checkbox[name=group_by]:checked").each(function(){
        group_by.push($(this).val());
    });
    var qry_str = '';
    qry_str = qry_str + '?tu_ngay=' + $('#tu_ngay').val();
    qry_str = qry_str + '&den_ngay=' + $('#den_ngay').val();
    qry_str = qry_str + '&loai_dvkt=' + $('#loai_dvkt').val();
    qry_str = qry_str + '&dvkt=' + $('#dvkt').val();
    qry_str = qry_str + '&department=' + $('#department').val();
    qry_str = qry_str + '&execute_room=' + $('#execute_room').val();
    qry_str = qry_str + '&execute_department=' + $('#execute_department').val();
    qry_str = qry_str + '&icd=' + $('#icd').val();
    qry_str = qry_str + '&request_room=' + $('#request_room').val();
    var url_str = "{{url('/')}}" + "/khth/dvkt-export-xls" + qry_str;
    url_str = url_str + "&group_by=" + group_by;
    window.location.href = url_str;
})

$(document).ready(function() {
    $('#dvkt').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả',
        ajax: {
            minimumInputLength : 3,
            url: "{{route('khth.get-danh-muc-dvkt')}}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term,
                    loai_dvkt: $('#loai_dvkt').val(),
                }
            },
            processResults: function (data, params) {
                return {
                    results: $.map(data, function (item){
                        return {
                            text: item.text,
                            id: item.id,
                        }
                    })
                };
            },
        },
    });

    $('#icd').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả',
        ajax: {
            minimumInputLength : 2,
            url: "{{route('khth.get-danh-muc-icd')}}",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    term: params.term,
                }
            },
            processResults: function (data, params) {
                return {
                    results: $.map(data, function (item){
                        return {
                            text: item.text,
                            id: item.id,
                        }
                    })
                };
            },
        },
    });
    $('#loai_dvkt').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('#department').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('#execute_room').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('#execute_department').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });
    $('#request_room').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('#dvkt-index').DataTable({
        "processing": true,
        "serverSide": true,
        "searchDelay": 1000,
        "responsive": true, // Giữ responsive
        "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
        "ajax": {
            url: "{{ route('khth.get-dvkt') }}",
            data: {
                tu_ngay: $('#tu_ngay').val(),
                den_ngay: $('#den_ngay').val(),
                loai_dvkt: $('#loai_dvkt').val(),
                dvkt: $('#dvkt').val(),
                department: $('#department').val(),
                execute_room: $('#execute_room').val(),
                execute_department: $('#execute_department').val(),
                icd: $('#icd').val(),
                request_room: $('#request_room').val(),
            }
        },
        "columns": [
            { "data": "tdl_treatment_code", "name": "tdl_treatment_code" },
            { "data": "tdl_patient_name", "name": "his_service_req.tdl_patient_name" },
            { "data": "tdl_patient_dob", "name": "his_service_req.tdl_patient_dob" },
            { "data": "tdl_intruction_time", "name": "tdl_intruction_time" },
            { "data": "hein_card_number", "name": "hein_card_number" },
            { "data": "tdl_service_name", "name": "tdl_service_name" },
            { "data": "execute_room_name", "name": "his_execute_room.execute_room_name" },
            { "data": "tdl_request_username", "name": "tdl_request_username" },
            { "data": "amount", "name": "amount" },
            { "data": "price", "name": "price" },
        ],
    });

    // $("#myform").submit(function() {
    //     event.preventDefault();
    //     $('#dvkt-index').DataTable().ajax.reload();
    // })
});

</script>
@endpush