<div id="menu14" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info14">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info14">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#other_info14">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Qd130Xml14 as $xml14)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info14" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml14->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Số giấy hẹn KQ</th>
                                    <td>{{ $xml14->so_giayhen_kl }}</td>
                                </tr>
                                <tr>
                                    <th>Mã CSKCB</th>
                                    <td>{{ $xml14->ma_cskcb }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên</th>
                                    <td>{{ $xml14->ho_ten }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh</th>
                                    <td>{{ strtodate($xml14->ngay_sinh) }}</td>
                                </tr>
                                <tr>
                                    <th>Giới tính</th>
                                    <td>{{ $xml14->gioi_tinh }}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ</th>
                                    <td>{{ $xml14->dia_chi }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ BHYT</th>
                                    <td>{{ $xml14->ma_the_bhyt }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>GT thẻ đến</th>
                                    <td>{{ strtodate($xml14->gt_the_den) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodate($xml14->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào nội trú</th>
                                    <td>{{ strtodate($xml14->ngay_vao_noi_tru) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodate($xml14->ngay_ra) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày hẹn KQ</th>
                                    <td>{{ strtodate($xml14->ngay_hen_kl) }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml14->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh chính</th>
                                    <td>{{ $xml14->ma_benh_chinh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh kèm theo</th>
                                    <td>{{ $xml14->ma_benh_kt }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã bệnh YHCT</th>
                                    <td>{{ $xml14->ma_benh_yhct }}</td>
                                </tr>
                                <tr>
                                    <th>Mã đối tượng KCB</th>
                                    <td>{{ $xml14->ma_doituong_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml14->ma_bac_si }}</td>
                                </tr>
                                <tr>
                                    <th>Mã TT dịch vụ</th>
                                    <td>{{ $xml14->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml14->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml14->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info14" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml14->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh chính</th>
                                    <td>{{ $xml14->ma_benh_chinh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh kèm theo</th>
                                    <td>{{ $xml14->ma_benh_kt }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh YHCT</th>
                                    <td>{{ $xml14->ma_benh_yhct }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml14->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml14->ghi_chu }}</td>
                                </tr>
                                <tr>
                                    <th>Mã TT dịch vụ</th>
                                    <td>{{ $xml14->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml14->ngay_ct) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info14" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã đối tượng KCB</th>
                                    <td>{{ $xml14->ma_doituong_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml14->ma_bac_si }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã thẻ BHYT</th>
                                    <td>{{ $xml14->ma_the_bhyt }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml14->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>