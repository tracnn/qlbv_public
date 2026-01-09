<div id="menu8" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info8">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info8">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#other_info8">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Xml3176Xml8 as $xml8)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info8" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml8->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Mã loại KCB</th>
                                    <td>{{ $xml8->ma_loai_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml8->ho_ten_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml8->ho_ten_me }}</td>
                                </tr>
                                <tr>
                                    <th>Người giám hộ</th>
                                    <td>{{ $xml8->nguoi_giam_ho }}</td>
                                </tr>
                                <tr>
                                    <th>Đơn vị</th>
                                    <td>{{ $xml8->don_vi }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodate($xml8->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodate($xml8->ngay_ra) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh con</th>
                                    <td>{{ strtodate($xml8->ngay_sinhcon) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày con chết</th>
                                    <td>{{ strtodate($xml8->ngay_conchet) }}</td>
                                </tr>
                                <tr>
                                    <th>Số con chết</th>
                                    <td>{{ $xml8->so_conchet }}</td>
                                </tr>
                                <tr>
                                    <th>Kết quả điều trị</th>
                                    <td>{{ $xml8->ket_qua_dtri }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml8->ghi_chu }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thủ trưởng đơn vị</th>
                                    <td>{{ $xml8->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml8->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ tạm</th>
                                    <td>{{ $xml8->ma_the_tam }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml8->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info8" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodatetime($xml8->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodatetime($xml8->ngay_ra) }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán vào</th>
                                    <td>{{ $xml8->chan_doan_vao }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml8->chan_doan_rv }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Quá trình bệnh lý</th>
                                    <td>{{ $xml8->qt_benhly }}</td>
                                </tr>
                                <tr>
                                    <th>Tóm tắt kết quả</th>
                                    <td>{{ $xml8->tomtat_kq }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml8->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml8->ghi_chu }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info8" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã cha</th>
                                    <td>{{ $xml8->ma_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Mã mẹ</th>
                                    <td>{{ $xml8->ma_me }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml8->ho_ten_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml8->ho_ten_me }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Số ngày nghỉ</th>
                                    <td>{{ $xml8->so_ngay_nghi }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú từ ngày</th>
                                    <td>{{ strtodate($xml8->ngoaitru_tungay) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú đến ngày</th>
                                    <td>{{ strtodate($xml8->ngoaitru_denngay) }}</td>
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