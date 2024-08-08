@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Khám sức khỏe
    <small>Danh sách</small>
</h1>
{{ Breadcrumbs::render('ksk.index') }}
@stop

@section('content')
@include('includes.message')
@include('khth.ksk..partials.search')
<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách hồ sơ
    </div>
    <div class="panel-body table-responsive">
        <table id="ksk-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <th>Mã điều trị</th>
                <th>Trạng thái</th>
                <th>Họ và tên</th>
                <th>Ngày sinh</th>
                <th>Giới tính</th>
                <th>Số điện thoại</th>
                <th>Tác vụ</th>
            </thead>
        </table>
    </div>
</div>

<!-- Modal form to edit a form the luc -->
<div id="editModalTheluc" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="id" >
                        <input type="hidden" class="form-control" id="dhst_id" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Mạch:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="pulse">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">lần/phút</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Huyết áp:</label>
                        <div class="col-sm-4">
                            <input type="number" class="form-control" id="blood_pressure_max">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <div class="col-sm-3">
                            <input type="number" class="form-control" id="blood_pressure_min">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2" for="title">mmHG</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Cân nặng:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="weight">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">kg</label>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Chiều cao:</label>
                        <div class="col-sm-7">
                            <input type="number" class="form-control" id="height">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                        <label class="control-label col-sm-2">cm</label>
                    </div>      
                    <div class="form-group">
                        <label class="control-label col-sm-3">Phân loại</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="note"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                              
                    </div>              
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" accesskey="s" onclick="khamtheluc()">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form kham noi -->
<div id="editModalNoi" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="service_req_id" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thể lực</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="theluc" disabled></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Tiền sử bệnh (Nếu có)</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="pathological_history"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Khám ngoại chung</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tuần hoàn</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_circulation"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Hô hấp</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_respiratory"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tiêu hóa</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_digestion"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div>                  
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Thận tiết niệu</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_kidney_urology"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Thần kinh</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_neurological"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Cơ xương khớp</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_muscle_bone"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Nội tiết</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_oend"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Tâm thần</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_mental"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Dinh dưỡng</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_nutrition"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div> 
                    <div class="form-group">
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Vận động</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_motion"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                            
                        </div>
                        <div class="col-sm-6">
                            <label class="control-label col-sm-3">Da liễu</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="part_exam_dermatology"></textarea>
                                <p class="errorTitle text-center alert alert-danger hidden"></p>
                            </div>                              
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="maukhamnoi" accesskey="l">
                        <span class="glyphicon glyphicon-plus"></span> Mẫu (Alt+L)
                    </button>
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="khamnoi()" accesskey="s">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form kham RHM -->
<div id="editModalRHM" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="rhm_id" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Hàm trên</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="part_exam_upper_jaw"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                     <div class="form-group">
                        <label class="control-label col-sm-3">Hàm dưới</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="part_exam_lower_jaw"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                     <div class="form-group">
                        <label class="control-label col-sm-3">Bệnh RHM (nếu có)</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="part_exam_stomatology"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="maukhamrhm" accesskey="l">
                        <span class="glyphicon glyphicon-plus"></span> Mẫu (Alt+L)
                    </button>
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="khamrhm()" accesskey="s">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form kham TMH -->
<div id="editModalTMH" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="tmh_id" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Tai phải:</label>
                        <div class="col-sm-10">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thường</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_ear_right_normal">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thầm</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_ear_right_whisper">
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
                                    <input type="number" class="form-control" id="part_exam_ear_left_normal">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Nói thầm</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_ear_left_whisper">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                              
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Tai:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="part_exam_ear"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Mũi:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="part_exam_nose"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">Họng:</label>
                        <div class="col-sm-10">
                            <textarea class="form-control" id="part_exam_throat"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="maukhamtmh" accesskey="l">
                        <span class="glyphicon glyphicon-plus"></span> Mẫu (Alt+L)
                    </button>
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="khamtmh()" accesskey="s">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form kham MAT -->
<div id="editModalMat" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="mat_id" >
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Nhãn áp:</label>
                        <div class="col-sm-9">
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Phải</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_eye_tension_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_eye_tension_left">
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
                                    <input type="number" class="form-control" id="part_exam_eyesight_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_eyesight_left">
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
                                    <input type="number" class="form-control" id="part_exam_eyesight_glass_right">
                                    <p class="errorTitle text-center alert alert-danger hidden"></p>
                                </div>                            
                            </div>
                            <div class="col-sm-6">
                                <label class="control-label col-sm-6">Trái</label>
                                <div class="col-sm-6">
                                    <input type="number" class="form-control" id="part_exam_eyesight_glass_left">
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
                                    <input type="checkbox" class="form-check-input" value="1" id="part_exam_eye_blind_color"> Bình thường
                                </label>
                                <div class="form-control">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="color_all"> Mù màu toàn bộ
                                    </label>&nbsp
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="color_red"> <span class="text-danger">Màu đỏ</span>
                                    </label>&nbsp                                  
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="color_green"> <span class="text-success">Màu xanh</span>
                                    </label>&nbsp
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" id="color_yellow"> <span class="text-warning">Màu vàng</span>
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
                                    <input type="checkbox" class="form-check-input" value="1" id="part_exam_horizontal_sight"> Bình thường
                                </label>
                            </div>                         
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Thị trường đứng:</label>
                        <div class="col-sm-9">
                            <div class="form-check-inline">
                                <label class="form-check-label form-control">
                                    <input type="checkbox" class="form-check-input" value="1" id="part_exam_vertical_sight"> Bình thường
                                </label>
                            </div>                         
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-3">Các bệnh về mắt:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="part_exam_eye"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>                            
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="maukhammat" accesskey="l">
                        <span class="glyphicon glyphicon-plus"></span> Mẫu (Alt+L)
                    </button>
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="khammat()" accesskey="s">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form kham san -->
<div id="editModalSan" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="san_id" >
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <textarea class="form-control" id="part_exam_obstetric" rows="8"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>      
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" id="maukhamsan" accesskey="l">
                        <span class="glyphicon glyphicon-plus"></span> Mẫu (Alt+L)
                    </button>
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="khamsan()" accesskey="s">
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
<!-- /Modal form to edit a form -->

<!-- Modal form to edit a form tư vấn -->
<div id="editModalTuvan" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <input type="hidden" class="form-control" id="tu_van_id" >
                    </div>
                    <div class="form-group">
                        <div class="col-sm-12">
                            <textarea class="form-control" id="next_treatment_instruction" rows="10"></textarea>
                            <p class="errorTitle text-center alert alert-danger hidden"></p>      
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary edit" data-dismiss="modal" onclick="tuvan()" accesskey="s">
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
<!-- /Modal form to edit a form -->

@include('khth.ksk.tong-ket')
@include('khth.ksk.tiep-don')
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;

    function fetchData(startDate, endDate) {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#ksk-index').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('ksk.get-danh-sach') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.ksk_contract = $('#ksk_contract').val();
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                {"data": "tdl_treatment_code", "name": "tdl_treatment_code"},
                {"data": "service_req_stt_name", "name": "his_service_req_stt.service_req_stt_name"},
                {"data": "tdl_patient_name", "name": "tdl_patient_name"},
                {"data": "tdl_patient_dob", "name": "tdl_patient_dob"},
                {"data": "tdl_patient_gender_name", "name": "tdl_patient_gender_name"},
                {"data": "tdl_patient_phone", "name": "tdl_patient_phone"},
                {"data": "action", "name": "action"},
            ],
            "oLanguage": {
              "sUrl": "{{asset('vendor/datatables/lang/vi.json')}}"
            },
        });

        table.ajax.reload();
    }
</script>

<script>
$(document).ready(function() {
    $('.select2').select2();
})

$(document).on('click', '.edit-modal-theluc', function() {
    $('.modal-title').html('Khám thể lực: ' + $(this).data('title'));
    $('#id').val($(this).data('id'));
    $('#dhst_id').val($(this).data('dhst_id'));
    $('#weight').val($(this).data('weight'));
    $('#height').val($(this).data('height'));
    $('#blood_pressure_max').val($(this).data('blood_pressure_max'));
    $('#blood_pressure_min').val($(this).data('blood_pressure_min'));
    $('#pulse').val($(this).data('pulse'));
    $('#note').val($(this).data('note'));
    $('#editModalTheluc').modal('show');
});

$(document).on('click', '.edit-modal-noi', function() {
    $('.modal-title').html('Khám nội, ngoại, da liễu: ' + $(this).data('title'));
    $('#service_req_id').val($(this).data('id'));
    $('#theluc').val($(this).data('theluc'));
    $('#pathological_history').val($(this).data('pathological_history'));
    $('#part_exam').val($(this).data('part_exam'));
    $('#part_exam_circulation').val($(this).data('part_exam_circulation'));
    $('#part_exam_respiratory').val($(this).data('part_exam_respiratory'));
    $('#part_exam_digestion').val($(this).data('part_exam_digestion'));
    $('#part_exam_kidney_urology').val($(this).data('part_exam_kidney_urology'));
    $('#part_exam_neurological').val($(this).data('part_exam_neurological'));
    $('#part_exam_muscle_bone').val($(this).data('part_exam_muscle_bone'));
    $('#part_exam_oend').val($(this).data('part_exam_oend'));
    $('#part_exam_mental').val($(this).data('part_exam_mental'));
    $('#part_exam_nutrition').val($(this).data('part_exam_nutrition'));
    $('#part_exam_motion').val($(this).data('part_exam_motion'));
    $('#part_exam_dermatology').val($(this).data('part_exam_dermatology'));
    $('#editModalNoi').modal('show');
});

$('#maukhamnoi').on('click', function() {
    
    if (!$('#part_exam').val()) {
        $('#part_exam').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_circulation').val()) {
        $('#part_exam_circulation').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_respiratory').val()) {
        $('#part_exam_respiratory').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_digestion').val()) {
        $('#part_exam_digestion').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_kidney_urology').val()) {
        $('#part_exam_kidney_urology').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_neurological').val()) {
        $('#part_exam_neurological').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_muscle_bone').val()) {
        $('#part_exam_muscle_bone').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_oend').val()) {
        $('#part_exam_oend').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_mental').val()) {
        $('#part_exam_mental').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_nutrition').val()) {
        $('#part_exam_nutrition').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_motion').val()) {
        $('#part_exam_motion').val('Bình thường' + '\r\n' + 'Loại I');
    }
    if (!$('#part_exam_dermatology').val()) {
        $('#part_exam_dermatology').val('Bình thường' + '\r\n' + 'Loại I');
    }

});

$(document).on('click', '.edit-modal-rhm', function() {
    $('.modal-title').html('Khám RHM: ' + $(this).data('title'));
    $('#rhm_id').val($(this).data('id'));
    $('#part_exam_upper_jaw').val($(this).data('part_exam_upper_jaw'));
    $('#part_exam_lower_jaw').val($(this).data('part_exam_lower_jaw'));
    $('#part_exam_stomatology').val($(this).data('part_exam_stomatology'));
    $('#editModalRHM').modal('show');
});

$('#maukhamrhm').on('click', function() {
    if (!$('#part_exam_upper_jaw').val()) {
        $('#part_exam_upper_jaw').val('Bình thường');
    }
    if (!$('#part_exam_lower_jaw').val()) {
        $('#part_exam_lower_jaw').val('Bình thường');
    }
    if (!$('#part_exam_stomatology').val()) {
        $('#part_exam_stomatology').val('Bình thường' + '\r\n' + 'Loại I');
    }
});

function khamtheluc() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-the-luc")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#id').val(),
            'dhst_id': $('#dhst_id').val(),
            'weight': $('#weight').val(),
            'height': $('#height').val(),
            'blood_pressure_max': $('#blood_pressure_max').val(),
            'blood_pressure_min': $('#blood_pressure_min').val(),
            'pulse': $('#pulse').val(),
            'note': $('#note').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

function khamnoi() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-noi")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#service_req_id').val(),
            'pathological_history': $('#pathological_history').val(),
            'part_exam': $('#part_exam').val(),
            'part_exam_circulation': $('#part_exam_circulation').val(),
            'part_exam_respiratory': $('#part_exam_respiratory').val(),
            'part_exam_digestion': $('#part_exam_digestion').val(),
            'part_exam_kidney_urology': $('#part_exam_kidney_urology').val(),
            'part_exam_neurological': $('#part_exam_neurological').val(),
            'part_exam_muscle_bone': $('#part_exam_muscle_bone').val(),
            'part_exam_oend': $('#part_exam_oend').val(),
            'part_exam_mental': $('#part_exam_mental').val(),
            'part_exam_nutrition': $('#part_exam_nutrition').val(),
            'part_exam_motion': $('#part_exam_motion').val(),
            'part_exam_dermatology': $('#part_exam_dermatology').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}
 
function khamrhm() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-rhm")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#rhm_id').val(),
            'part_exam_upper_jaw': $('#part_exam_upper_jaw').val(),
            'part_exam_lower_jaw': $('#part_exam_lower_jaw').val(),
            'part_exam_stomatology': $('#part_exam_stomatology').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

$(document).on('click', '.edit-modal-tmh', function() {
    $('.modal-title').html('Khám TMH: ' + $(this).data('title'));
    $('#tmh_id').val($(this).data('id'));
    $('#part_exam_ear_right_normal').val($(this).data('part_exam_ear_right_normal'));
    $('#part_exam_ear_right_whisper').val($(this).data('part_exam_ear_right_whisper'));
    $('#part_exam_ear_left_normal').val($(this).data('part_exam_ear_left_normal'));
    $('#part_exam_ear_left_whisper').val($(this).data('part_exam_ear_left_whisper'));
    $('#part_exam_ear').val($(this).data('part_exam_ear'));
    $('#part_exam_nose').val($(this).data('part_exam_nose'));
    $('#part_exam_throat').val($(this).data('part_exam_throat'));
    $('#editModalTMH').modal('show');
});

$('#maukhamtmh').on('click', function() {
    if (!$('#part_exam_ear_right_normal').val()) {
        $('#part_exam_ear_right_normal').val('5');
    }
    if (!$('#part_exam_ear_right_whisper').val()) {
        $('#part_exam_ear_right_whisper').val('0.5');
    }
    if (!$('#part_exam_ear_left_normal').val()) {
        $('#part_exam_ear_left_normal').val('5');
    }
    if (!$('#part_exam_ear_left_whisper').val()) {
        $('#part_exam_ear_left_whisper').val('0.5');
    }
    if (!$('#part_exam_throat').val()) {
        $('#part_exam_throat').val('Bình thường' + '\r\n' + 'Loại I');
    }
});

function khamtmh() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-tmh")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#tmh_id').val(),
            'part_exam_ear_right_normal': $('#part_exam_ear_right_normal').val(),
            'part_exam_ear_right_whisper': $('#part_exam_ear_right_whisper').val(),
            'part_exam_ear_left_normal': $('#part_exam_ear_left_normal').val(),
            'part_exam_ear_left_whisper': $('#part_exam_ear_left_whisper').val(),
            'part_exam_ear': $('#part_exam_ear').val(),
            'part_exam_nose': $('#part_exam_nose').val(),
            'part_exam_throat': $('#part_exam_throat').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

$(document).on('click', '.edit-modal-mat', function() {

    $('#part_exam_eye_blind_color').prop('checked', false);
    $('#color_all').prop('checked', false);
    $('#color_red').prop('checked', false);
    $('#color_green').prop('checked', false);
    $('#color_yellow').prop('checked', false);
    switch ($(this).data('part_exam_eye_blind_color'))
    {
        case 1: {
            $('#part_exam_eye_blind_color').prop('checked', true);
            break;
        }
        case 2: {
            $('#color_all').prop('checked', true);
            break;
        }
        case 3: {
            $('#color_red').prop('checked', true);
            break;
        }
        case 4: {
            $('#color_green').prop('checked', true);
            break;
        }
        case 5: {
            $('#color_yellow').prop('checked', true);
            break;
        }
        case 6: {
            $('#color_red').prop('checked', true);
            $('#color_green').prop('checked', true);
            break;
        }
        case 7: {
            $('#color_red').prop('checked', true);
            $('#color_yellow').prop('checked', true);
            break;
        }
        case 8: {
            $('#color_green').prop('checked', true);
            $('#color_yellow').prop('checked', true);
            break;
        }
        case 9: {
            $('#color_red').prop('checked', true);
            $('#color_green').prop('checked', true);
            $('#color_yellow').prop('checked', true);
            break;
        }
        default : {
        }
    }

    if ($(this).data('part_exam_horizontal_sight')=='1') {
        $('#part_exam_horizontal_sight').prop('checked', true);
    } else {
        $('#part_exam_horizontal_sight').prop('checked', false);
    }
    if ($(this).data('part_exam_vertical_sight')=='1') {
        $('#part_exam_vertical_sight').prop('checked', true);
    } else {
        $('#part_exam_vertical_sight').prop('checked', false);
    }    
    $('.modal-title').html('Khám Mắt: ' + $(this).data('title'));
    $('#mat_id').val($(this).data('id'));
    $('#part_exam_horizontal_sight').val($(this).data('part_exam_horizontal_sight'));
    $('#part_exam_vertical_sight').val($(this).data('part_exam_vertical_sight'));
    $('#part_exam_eye').val($(this).data('part_exam_eye'));
    $('#part_exam_eye_tension_left').val($(this).data('part_exam_eye_tension_left'));
    $('#part_exam_eye_tension_right').val($(this).data('part_exam_eye_tension_right'));
    $('#part_exam_eyesight_left').val($(this).data('part_exam_eyesight_left'));
    $('#part_exam_eyesight_right').val($(this).data('part_exam_eyesight_right'));
    $('#part_exam_eyesight_glass_left').val($(this).data('part_exam_eyesight_glass_left'));
    $('#part_exam_eyesight_glass_right').val($(this).data('part_exam_eyesight_glass_right'));
    $('#editModalMat').modal('show');
});

$('#part_exam_eye_blind_color').on('change', function() {
    if ($(this).is(':checked')) {
        $('#color_all').prop('checked', false);
        $('#color_red').prop('checked', false);
        $('#color_green').prop('checked', false);
        $('#color_yellow').prop('checked', false);
    }
});

$('#color_all').on('change', function() {
    if ($(this).is(':checked')) {
        $('#part_exam_eye_blind_color').prop('checked', false);
        $('#color_red').prop('checked', false);
        $('#color_green').prop('checked', false);
        $('#color_yellow').prop('checked', false);
    }
});

$('#color_red').on('change', function() {
    if ($(this).is(':checked')) {
        $('#part_exam_eye_blind_color').prop('checked', false);
        $('#color_all').prop('checked', false);
    }
});

function khammat() {
    var $part_exam_eye_blind_color = 0;
    if ($('#part_exam_eye_blind_color').is(':checked')) {
        $part_exam_eye_blind_color = 1;
    };
    if ($('#color_all').is(':checked')) {
        $part_exam_eye_blind_color = 2;
    };
    if ($('#color_red').is(':checked')) {
        $part_exam_eye_blind_color = 3;
    };
    if ($('#color_green').is(':checked')) {
        $part_exam_eye_blind_color = 4;
    };
    if ($('#color_yellow').is(':checked')) {
        $part_exam_eye_blind_color = 5;
    };
    if ($('#color_red').is(':checked') && $('#color_green').is(':checked')) {
        $part_exam_eye_blind_color = 6;
    };
    if ($('#color_red').is(':checked') && $('#color_yellow').is(':checked')) {
        $part_exam_eye_blind_color = 7;
    };
    if ($('#color_green').is(':checked') && $('#color_yellow').is(':checked')) {
        $part_exam_eye_blind_color = 8;
    };
    if ($('#color_red').is(':checked') && $('#color_green').is(':checked') && $('#color_yellow').is(':checked')) {
        $part_exam_eye_blind_color = 9;
    };

    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-mat")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#mat_id').val(),
            'part_exam_eye_blind_color': $part_exam_eye_blind_color,
            'part_exam_horizontal_sight': $('#part_exam_horizontal_sight').prop("checked") ? 1 : 2,
            'part_exam_vertical_sight': $('#part_exam_vertical_sight').prop("checked") ? 1 : 2,
            'part_exam_eye': $('#part_exam_eye').val(),
            'part_exam_eye_tension_left': $('#part_exam_eye_tension_left').val(),
            'part_exam_eye_tension_right': $('#part_exam_eye_tension_right').val(),
            'part_exam_eyesight_left': $('#part_exam_eyesight_left').val(),
            'part_exam_eyesight_right': $('#part_exam_eyesight_right').val(),
            'part_exam_eyesight_glass_left': $('#part_exam_eyesight_glass_left').val(),
            'part_exam_eyesight_glass_right': $('#part_exam_eyesight_glass_right').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

$(document).on('click', '.edit-modal-san', function() {
    $('.modal-title').html('Khám sản phụ khoa: ' + $(this).data('title'));
    $('#san_id').val($(this).data('id'));
    $('#part_exam_obstetric').val($(this).data('part_exam_obstetric'));
    $('#editModalSan').modal('show');
});

function khamsan() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.kham-san")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#san_id').val(),
            'part_exam_obstetric': $('#part_exam_obstetric').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

$('#maukhamsan').on('click', function() {
    if (!$('#part_exam_obstetric').val()) {
        $('#part_exam_obstetric').val(
            'Âm hộ, âm đạo: Bình thường' + '\r\n'
            + 'Cổ tử cung: Không tổn thương' + '\r\n'
            + 'Tử cung, phần phụ: Bình thường' + '\r\n'
            + 'Tiền sử mổ đẻ: ' + '\r\n'
            + 'Kết luận: Sản phụ khoa bình thường' + '\r\n'
            + 'Phân loại: Loại I' 
        );
    }
});

$('#maukhammat').on('click', function() {
    if (!$('#part_exam_eye').val()) {
        $('#part_exam_eye').val('Bình thường' + '\r\n' + 'Loại I');
    }
});

$(document).on('click', '.edit-modal-tuvan', function() {
    $('.modal-title').html('Tư vấn: ' + $(this).data('title'));
    $('#tu_van_id').val($(this).data('tu_van_id'));

    $('#next_treatment_instruction').val($(this).data('next_treatment_instruction'));
    $('#editModalTuvan').modal('show');
});

function tuvan() {
    $("#loading_center").show();
    $.ajax({
        type: 'POST',
        url: '{{route("ksk.tuvan")}}',
        data: {
            '_token': $('input[name=_token]').val(),
            'id': $('#tu_van_id').val(),
            'next_treatment_instruction': $('#next_treatment_instruction').val(),
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
            $('#ksk-index').DataTable().ajax.reload();
        },
        complete: function() {
            // Hide loading spinner
            $("#loading_center").hide();
        }
    });
}

</script>

@endpush