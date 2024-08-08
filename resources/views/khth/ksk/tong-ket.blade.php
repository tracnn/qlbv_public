<!-- Modal form to edit a form tong ket -->
<div id="editModalTongket" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="tongket_id">
                        <input type="hidden" class="form-control" id="service_req_stt_id">
                        <input type="hidden" class="form-control" id="treatment_id">
                    </div>
                    <label class="col-sm-12 label-info">Thể lực</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Mạch:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="view_pulse">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">lần/phút</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Huyết áp:</label>
                        <div class="col-sm-4">
                            <input type="number" class="form-control" id="view_blood_pressure_max">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <div class="col-sm-3">
                            <input type="number" class="form-control" id="view_blood_pressure_min">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">mmHG</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Cân nặng:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="view_weight">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">kg</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Chiều cao:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="view_height">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">cm</label>
                    </div>      
                    <div class="form-group">
                        <label class="control-label col-sm-3">Phân loại</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="view_note"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                              
                    </div>    

                    <label class="col-sm-12 label-info">Nội, ngoại, da liễu</label>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Khám ngoại chung</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tuần hoàn</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_circulation"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Hô hấp</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_respiratory"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tiêu hóa</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_digestion"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div>                  
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Thận tiết niệu</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_kidney_urology"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Thần kinh</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_neurological"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Cơ xương khớp</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_muscle_bone"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Nội tiết</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_oend"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tâm thần</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_mental"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Dinh dưỡng</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_nutrition"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Vận động</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_motion"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Da liễu</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="view_part_exam_dermatology"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>   
                    </div>  
                    <label class="col-sm-12 label-info">Răng hàm mặt</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Hàm trên</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="view_part_exam_upper_jaw"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                     <div class="form-group">
                        <label class="control-label col-sm-3">Hàm dưới</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="view_part_exam_lower_jaw"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                     <div class="form-group">
                        <label class="control-label col-sm-3">Bệnh RHM (nếu có)</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="view_part_exam_stomatology"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <label class="col-sm-12 label-info">Tai mũi họng</label>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Tai phải:</label>
                        <div class="col-sm-10">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thường</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_ear_right_normal">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thầm</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_ear_right_whisper">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Tai trái:</label>
                        <div class="col-sm-10">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thường</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_ear_left_normal">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thầm</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_ear_left_whisper">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Tai:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="view_part_exam_ear"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Mũi:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="view_part_exam_nose"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Họng:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="view_part_exam_throat"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <label class="col-sm-12 label-info">Mắt</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Nhãn áp:</label>
                        <div class="col-sm-9">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Phải</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eye_tension_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eye_tension_left">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thị lực không kính:</label>
                        <div class="col-sm-9">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Phải</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eyesight_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eyesight_left">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thị lực có kính:</label>
                        <div class="col-sm-9">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Phải</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eyesight_glass_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="view_part_exam_eyesight_glass_left">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Sắc giác:</label>
                        <div class="col-sm-9">
                            <div class="form-check-inline">
                                <label class="form-check-label form-control">
                                    <input type="checkbox" class="form-check-input" value="1" id="view_part_exam_eye_blind_color"> Bình thường
                                </label>
                                <div class="form-control">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="view_color_all"> Mù màu toàn bộ
                                    </label>&nbsp
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="view_color_red"> <span class="text-danger">Màu đỏ</span>
                                    </label>&nbsp                                  
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="view_color_green"> <span class="text-success">Màu xanh</span>
                                    </label>&nbsp
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="view_color_yellow"> <span class="text-warning">Màu vàng</span>
                                    </label>  
                                </div>
                            </div>                         
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thị trường ngang:</label>
                        <div class="col-sm-9">
                            <div class="form-check-inline">
                                <label class="form-check-label form-control">
                                    <input type="checkbox" class="form-check-input" value="1" id="view_part_exam_horizontal_sight"> Bình thường
                                </label>
                            </div>                         
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thị trường đứng:</label>
                        <div class="col-sm-9">
                            <div class="form-check-inline">
                                <label class="form-check-label form-control">
                                    <input type="checkbox" class="form-check-input" value="1" id="view_part_exam_vertical_sight"> Bình thường
                                </label>
                            </div>                         
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Các bệnh về mắt:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="view_part_exam_eye"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <label class="col-sm-12 label-info">Sản phụ khoa</label>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <textarea class="form-control" id="view_part_exam_obstetric" rows="8"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>      
                        </div>         
                    </div>
                    <label class="col-sm-12 label-info">Kết quả CLS</label>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <table class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Loại dịch vụ</th>
                                        <th>Trạng thái</th>
                                    </tr>
                                </thead>
                                <tbody id="kq_cls"></tbody>
                            </table>
                        </div>         
                    </div>
                    <label class="col-sm-12 label-danger">Tổng kết</label>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Tóm tắt KQ CLS</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="subclinical" rows="8"></textarea>
                        </div>        
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3"><span class="label-danger">Chọn mẫu KQ CLS</span></label>
                        <div class="col-sm-9">
                            <button type="button" class="btn btn-info" id="ksklx">
                                <span class="glyphicon glyphicon-plus"></span> Mẫu KSK lái xe
                            </button>
                            <button type="button" class="btn btn-info" id="kskdl">
                                <span class="glyphicon glyphicon-plus"></span> Mẫu KSK đi làm
                            </button>
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Phân loại</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="health_exam_rank_id">
                                <option></option>
                                @foreach($health_rank as $key => $value)
                                <option value="{{$value->id}}">{{$value->health_exam_rank_name}}</option>
                                @endforeach
                            </select>
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Ghi chú</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="service_req_note"></textarea>
                        </div>         
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">PP điều trị (nếu có)</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="treatment_instruction"></textarea>
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" id="save" onclick="khamtongket()">
                        <span class='glyphicon glyphicon-check'></span> Lưu
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal" id="close">
                        <span class='glyphicon glyphicon-remove'></span> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to edit a form -->

@push('after-scripts')
<script type="text/javascript">
$(document).on('click', '.edit-modal-tongket', function() {
    var title = $(this).data('tdl_patient_avatar_url') ? 
    '<span class="label label-success">(ĐÃ CHỤP ẢNH)</span>' : 
    '<span class="label label-danger">(CHƯA CHỤP ẢNH)</span>';
    $('.modal-title').html('Tổng kết: ' + $(this).data('title')  + title);
    $('#tongket_id').val($(this).data('id'));
    $('#treatment_id').val($(this).data('treatment_id'));
    $('#view_weight').val($(this).data('weight'));
    $('#view_height').val($(this).data('height'));
    $('#view_blood_pressure_max').val($(this).data('blood_pressure_max'));
    $('#view_blood_pressure_min').val($(this).data('blood_pressure_min'));
    $('#view_pulse').val($(this).data('pulse'));
    $('#view_note').val($(this).data('note'));
    $('#view_part_exam').val($(this).data('part_exam'));
    $('#view_part_exam_circulation').val($(this).data('part_exam_circulation'));
    $('#view_part_exam_respiratory').val($(this).data('part_exam_respiratory'));
    $('#view_part_exam_digestion').val($(this).data('part_exam_digestion'));
    $('#view_part_exam_kidney_urology').val($(this).data('part_exam_kidney_urology'));
    $('#view_part_exam_neurological').val($(this).data('part_exam_neurological'));
    $('#view_part_exam_muscle_bone').val($(this).data('part_exam_muscle_bone'));
    $('#view_part_exam_oend').val($(this).data('part_exam_oend'));
    $('#view_part_exam_mental').val($(this).data('part_exam_mental'));
    $('#view_part_exam_nutrition').val($(this).data('part_exam_nutrition'));
    $('#view_part_exam_motion').val($(this).data('part_exam_motion'));
    $('#view_part_exam_dermatology').val($(this).data('part_exam_dermatology'));
    $('#view_part_exam_upper_jaw').val($(this).data('part_exam_upper_jaw'));
    $('#view_part_exam_lower_jaw').val($(this).data('part_exam_lower_jaw'));
    $('#view_part_exam_stomatology').val($(this).data('part_exam_stomatology'));
    $('#view_part_exam_ear_right_normal').val($(this).data('part_exam_ear_right_normal'));
    $('#view_part_exam_ear_right_whisper').val($(this).data('part_exam_ear_right_whisper'));
    $('#view_part_exam_ear_left_normal').val($(this).data('part_exam_ear_left_normal'));
    $('#view_part_exam_ear_left_whisper').val($(this).data('part_exam_ear_left_whisper'));
    $('#view_part_exam_ear').val($(this).data('part_exam_ear'));
    $('#view_part_exam_nose').val($(this).data('part_exam_nose'));
    $('#view_part_exam_throat').val($(this).data('part_exam_throat'));
    
    $('#view_part_exam_eye_blind_color').prop('checked', false);
    $('#view_color_all').prop('checked', false);
    $('#view_color_red').prop('checked', false);
    $('#view_color_green').prop('checked', false);
    $('#view_color_yellow').prop('checked', false);
    switch ($(this).data('part_exam_eye_blind_color'))
    {
        case 1: {
            $('#view_part_exam_eye_blind_color').prop('checked', true);
            break;
        }
        case 2: {
            $('#view_color_all').prop('checked', true);
            break;
        }
        case 3: {
            $('#view_color_red').prop('checked', true);
            break;
        }
        case 4: {
            $('#view_color_green').prop('checked', true);
            break;
        }
        case 5: {
            $('#view_color_yellow').prop('checked', true);
            break;
        }
        case 6: {
            $('#view_color_red').prop('checked', true);
            $('#view_color_green').prop('checked', true);
            break;
        }
        case 7: {
            $('#view_color_red').prop('checked', true);
            $('#view_color_yellow').prop('checked', true);
            break;
        }
        case 8: {
            $('#view_color_green').prop('checked', true);
            $('#view_color_yellow').prop('checked', true);
            break;
        }
        case 9: {
            $('#view_color_red').prop('checked', true);
            $('#view_color_green').prop('checked', true);
            $('#view_color_yellow').prop('checked', true);
            break;
        }
        default : {
        }
    }

    if ($(this).data('service_req_stt_id')==2) {
        $('#save').show();
    } else {
        $('#save').hide();
    }

    if ($(this).data('part_exam_horizontal_sight')=='1') {
        $('#view_part_exam_horizontal_sight').prop('checked', true);
    } else {
        $('#view_part_exam_horizontal_sight').prop('checked', false);
    }
    if ($(this).data('part_exam_vertical_sight')=='1') {
        $('#view_part_exam_vertical_sight').prop('checked', true);
    } else {
        $('#view_part_exam_vertical_sight').prop('checked', false);
    }    
    
    $('#health_exam_rank_id').val($(this).data('health_exam_rank_id'));
    
    $('#view_part_exam_horizontal_sight').val($(this).data('part_exam_horizontal_sight'));
    $('#view_part_exam_vertical_sight').val($(this).data('part_exam_vertical_sight'));
    $('#view_part_exam_eye').val($(this).data('part_exam_eye'));
    $('#view_part_exam_eye_tension_left').val($(this).data('part_exam_eye_tension_left'));
    $('#view_part_exam_eye_tension_right').val($(this).data('part_exam_eye_tension_right'));
    $('#view_part_exam_eyesight_left').val($(this).data('part_exam_eyesight_left'));
    $('#view_part_exam_eyesight_right').val($(this).data('part_exam_eyesight_right'));
    $('#view_part_exam_eyesight_glass_left').val($(this).data('part_exam_eyesight_glass_left'));
    $('#view_part_exam_eyesight_glass_right').val($(this).data('part_exam_eyesight_glass_right'));
    $('#view_part_exam_obstetric').val($(this).data('part_exam_obstetric'));
    $('#service_req_note').val($(this).data('service_req_note'));
    $('#treatment_instruction').val($(this).data('treatment_instruction'));
    $('#service_req_stt_id').val($(this).data('service_req_stt_id'));
    $('#subclinical').val($(this).data('subclinical'));
    $('#editModalTongket').modal('show');

    $("#loading_center").show();
    $.ajax({
        type: 'GET',
        url: '{{route("ksk.kq-cls")}}',
        data: {
            'id': $('#treatment_id').val()
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
                    $('#kq_cls').html(data);
                }
            }
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });  
});

function khamtongket() {
    if (window.confirm("Bạn có chắc chắn không?")) 
    {
        $("#loading_center").show();
        $.ajax({
            type: 'POST',
            url: '{{route("ksk.kham-tongket")}}',
            data: {
                '_token': $('input[name=_token]').val(),
                'id': $('#tongket_id').val(),
                'health_exam_rank_id': $('#health_exam_rank_id').val(),
                'service_req_note': $('#service_req_note').val(),
                'treatment_instruction': $('#treatment_instruction').val(),
                'service_req_stt_id': $('#service_req_stt_id').val(),
                'subclinical': $('#subclinical').val(),
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
                        toastr.success(data.noiDung);
                    }
                }
                table.ajax.reload();
                //$('#ksk-index').DataTable().ajax.reload();
            },
            complete: function() {
                // Hide loading spinner
                $("#loading_center").hide();
            }
        });        
    }

}

$('#ksklx').on('click', function() {
    if (!$('#subclinical').val()) {
        $('#subclinical').val(
            '1. Các xét nghiệm bắt buộc' + '\r\n'
            + 'a) Xét nghiệm ma túy: Âm tính' + '\r\n'
            + '- Test Morphin/Heroin: Âm tính' + '\r\n'
            + '- Test Amphetamin: Âm tính' + '\r\n'
            + '- Test Marijuana (cần sa): Âm tính' + '\r\n'
            + 'b) Xét nghiệm nồng độ cồn trong máu hoặc hơi thở: 0,0 %' + '\r\n'
            + '2. Các xét nghiệm chỉ thực hiện khi có chỉ định của bác sỹ khám sức khỏe: Huyết học/sinh hóa/X.quang và các xét nghiệm khác' + '\r\n'
            + 'a) Kết quả:' + '\r\n'
            + 'a) Kết luận:' + '\r\n'
        );
    }
});

$('#kskdl').on('click', function() {
    if (!$('#subclinical').val()) {
        $('#subclinical').val(
            '1. Xét nghiệm máu: ' + '\r\n'
            + 'a) Công thức máu: ' + '\r\n'
            + '- Số lượng Hồng cầu: 4,0 T/l ' + '\r\n'
            + '- Số lượng Bạch cầu: 4,5 G/l' + '\r\n'
            + '- Số lượng Tiểu cầu: 150 G/l' + '\r\n'
            + 'b) Sinh hóa máu: ' + '\r\n'
            + '- Đường máu: 4,02 mmol/l' + '\r\n'
            + '- Urê: ' + '\r\n'
            + '- Creatinin: ' + '\r\n'
            + '- ASAT (GOT): ' + '\r\n'
            + '- ALAT (GPT): ' + '\r\n'
            + 'c) Khác (nếu có): ' + '\r\n'
            + '2. Xét nghiệm nước tiểu: ' + '\r\n'
            + 'a) Đường: Âm tính' + '\r\n'
            + 'b) Prôtêin: Âm tính' + '\r\n'
            + 'c) Khác (nếu có): Âm tính' + '\r\n'
            + '3. Chẩn đoán hình ảnh: ' + '\r\n'
        );
    }
});

$('#health_exam_rank_id').on('change', function () {   
    switch($('#health_exam_rank_id').val()) 
    {
        case "1": 
        $('#service_req_note').val('HIỆN TẠI ĐỦ SỨC KHỎE HỌC TẬP VÀ LÀM VIỆC');
        break;
        case "2": 
        $('#service_req_note').val('HIỆN TẠI ĐỦ SỨC KHỎE HỌC TẬP VÀ LÀM VIỆC');
        break;
        case "3": 
        $('#service_req_note').val('HIỆN TẠI ĐỦ SỨC KHỎE HỌC TẬP VÀ LÀM VIỆC');
        break;
        case "4": 
        $('#service_req_note').val('HIỆN TẠI CÓ THỂ THỰC HIỆN MỘT SỐ VIỆC ĐƠN GIẢN');
        break;
        case "5": 
        $('#service_req_note').val('HIỆN TẠI KHÔNG ĐỦ SỨC KHỎE HỌC TẬP VÀ LÀM VIỆC');
        break;
        default:
        $('#service_req_note').val('CHƯA CHỌN PHÂN LOẠI');
    }
});
</script>
@endpush