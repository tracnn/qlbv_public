@push('after-styles')
<style>
 .my_camera {
     width: 270px;
     height: 200px;
}
</style>
@endpush
<!-- Modal form to tiepdon -->
<div id="editModalTiepdon" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" onclick="close_tiepdon()">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="tiepdon_id">
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <div id="my_camera"></div>
                        </div>  
                        <div class="col-sm-6">
                            <strong class="label label-info">Ảnh đã chụp</strong><br>
                            <img class="form-control my_camera" id="avatar" src="" height="100%" width="100%">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <button type="button" class="btn btn-info form-control" onclick="take_snapshot()">
                                <span class='glyphicon glyphicon-camera'></span> Chụp ảnh
                            </button>
                        </div>
                    </div>
                    <label class="col-sm-12 label-danger">Nhập thông tin bổ sung</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Chọn một lý do</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="hospitalization_reason_select">
                                <option></option>
                                <option value="Khám sức khỏe đi làm">Khám sức khỏe đi làm</option>
                                <option value="Khám sức khỏe đi học">Khám sức khỏe đi học</option>
                                <option value="Khám sức khỏe định kỳ">Khám sức khỏe định kỳ</option>
                                <option value="Khám sức khỏe chế độ">Khám sức khỏe chế độ</option>
                                <option value="Khám sức khỏe để bổ nhiệm">Khám sức khỏe để bổ nhiệm</option>
                            </select>
                        </div>  
                        <label class="control-label col-sm-3">Hoặc tự nhập</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="hospitalization_reason">
                        </div>       
                    </div>
                    <label class="col-sm-12 label-success">Thông tin hành chính</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Địa chỉ</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="address">
                            <input type="text" class="form-control" disabled="" id="vir_address">
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Địa chỉ hiện tại</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="ht_address">
                            <input type="text" class="form-control" disabled="" id="vir_ht_address">
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Địa chỉ nơi làm việc</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="work_place_id">
                                @foreach($work_place as $key => $value)
                                <option value="{{$value->id}}">{{$value->work_place_name}}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control" id="work_place">
                        </div>             
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Số CMND/CCCD</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="giay_tuy_than">
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Số điện thoại</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="phone">
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-dismiss="modal" onclick="tiepdon()">
                        <span id="save" class='glyphicon glyphicon-plus'></span> Đồng ý
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal" onclick="close_tiepdon()">
                        <span class='glyphicon glyphicon-remove'></span> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to delete a form -->

@push('after-scripts')
<script src="{{asset('vendor/webcamjs-master/webcam.min.js')}}"></script>
<script type="text/javascript">
$(document).on('click', '.edit-modal-tiepdon', function() {
    $('#avatar').attr("src", null);
    $('#address').val("");
    $('#vir_address').val("");
    $('#ht_address').val("");
    $('#vir_ht_address').val("");
    $('#phone').val("");
    $('#work_place_id').val("");
    $('#work_place').val("");
    $('#giay_tuy_than').val("");

    if ($(this).data('tdl_patient_avatar_url_tiepdon')) {
        $("#loading_center").show();
        $.ajax({
            type: 'GET',
            url: '{{route("ksk.download-avatar")}}',
            data: {
                'id': $(this).data('tdl_patient_avatar_url_tiepdon')
            },
            success: function(data) {
                switch (data.maKetqua)
                {
                    case '500': {
                        toastr.error(data.noiDung);
                        break;
                    }
                    case '400': {
                        toastr.warning(data.noiDung);
                        break;
                    }
                    default : {
                        $('#avatar').attr("src", data);
                    }
                }
            },
            complete: function() {
                // Hide loading spinner
                $("#loading_center").hide();
            }
        });   
    }

    if ($(this).data('id')) {
        $("#loading_center").show();
        $.ajax({
            type: 'GET',
            url: '{{route("ksk.get-patient")}}',
            dataType: 'json',
            data: {
                'id': $(this).data('id')
            },
            success: function(data) {
                switch (data.maKetqua)
                {
                    case '500': {
                        toastr.error(data.noiDung);
                        break;
                    }
                    case '400': {
                        toastr.warning(data.noiDung);
                        break;
                    }
                    default : {
                        $('#address').val(data.address);
                        $('#vir_address').val(data.vir_address);
                        $('#ht_address').val(data.ht_address);
                        $('#vir_ht_address').val(data.vir_ht_address);
                        $('#phone').val(data.phone);
                        $('#work_place_id').val(data.work_place_id).trigger('change');
                        $('#work_place').val(data.work_place);
                        $('#giay_tuy_than').val(data.giay_tuy_than);
                        $('#hospitalization_reason').val(data.hospitalization_reason);
                    }
                }
            },
            complete: function() {
                // Hide loading spinner
                $("#loading_center").hide();
            }
        });   
    }

    Webcam.set({
        width: 300,
        height: 220,
        image_format: 'jpeg',
        jpeg_quality: 100,
        constraints: {
            video: true,
            facingMode: "environment"
        }
    });
    Webcam.attach('#my_camera');
    $('.modal-title').html('Tiếp đón: ' + $(this).data('title'));
    $('#tiepdon_id').val($(this).data('id'));
    $('#editModalTiepdon').modal('show');
});

function tiepdon() {
    Webcam.reset('#my_camera');
    $('#loading_center').show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.tiepdon")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#tiepdon_id').val(),
            'img': $('#avatar').attr('src'),
            'address': $('#address').val(),
            'ht_address': $('#ht_address').val(),
            'phone': $('#phone').val(),
            'work_place_id': $('#work_place_id').val(),
            'work_place': $('#work_place').val(),
            'giay_tuy_than': $('#giay_tuy_than').val(),
            'hospitalization_reason': $('#hospitalization_reason').val(),
        },
        success: function(data) {
            switch (data.maKetqua)
            {
                case '500': {
                    console.log(data.noiDung);
                    toastr.error(data.noiDung);
                    break;
                }
                case '400': {
                    toastr.warning(data.noiDung);
                    break;
                }
                default : {
                    toastr.success(data.noiDung);
                }
            }
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });        
}

function close_tiepdon() {
    Webcam.reset('#my_camera');
}

function take_snapshot() {
    Webcam.snap(function(data_uri) {
        $('#avatar').attr("src", data_uri);
    });
}

$(document).ready(function() {
    $('#work_place_id').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Chọn một...'
    });
    $('#hospitalization_reason_select').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Chọn một...'
    });

    $('#hospitalization_reason_select').on('change', function (e) {
        $('#hospitalization_reason').val(this.value);
    });
});
</script>
@endpush