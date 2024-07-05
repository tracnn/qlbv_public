<div id="menu9" class="tab-pane fade">
<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#admin_info9">Thông tin hành chính</a></li>
    <li><a data-toggle="tab" href="#treatment_info9">Quá trình điều trị</a></li>
    <li><a data-toggle="tab" href="#other_info9">Thông tin khác</a></li>
</ul>
@foreach($xml1->Qd130Xml9 as $xml9)
<div class="tab-content">
    <!-- Thông tin hành chính -->
    <div id="admin_info9" class="tab-pane fade in active">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <div class="row">
                    <div class="col-md-4">
                        <table class="table table-hover">
                            <tr>
                                <th>Mã LK</th>
                                <td>{{ $xml9->ma_lk }}</td>
                            </tr>
                            <tr>
                                <th>Mã BHXH NND</th>
                                <td>{{ $xml9->ma_bhxh_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Mã thẻ NND</th>
                                <td>{{ $xml9->ma_the_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Họ tên NND</th>
                                <td>{{ $xml9->ho_ten_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Ngày sinh NND</th>
                                <td>{{ strtodate($xml9->ngaysinh_nnd) }}</td>
                            </tr>
                            <tr>
                                <th>Mã dân tộc NND</th>
                                <td>{{ $xml9->ma_dantoc_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Số CCCD NND</th>
                                <td>{{ $xml9->so_cccd_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Ngày cấp CCCD NND</th>
                                <td>{{ strtodate($xml9->ngaycap_cccd_nnd) }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <table class="table table-hover">
                            <tr>
                                <th>Nơi cấp CCCD NND</th>
                                <td>{{ $xml9->noicap_cccd_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Nơi cư trú NND</th>
                                <td>{{ $xml9->noi_cu_tru_nnd }}</td>
                            </tr>
                            <tr>
                                <th>Mã quốc tịch</th>
                                <td>{{ $xml9->ma_quoctich }}</td>
                            </tr>
                            <tr>
                                <th>Mã tỉnh cư trú</th>
                                <td>{{ $xml9->matinh_cu_tru }}</td>
                            </tr>
                            <tr>
                                <th>Mã huyện cư trú</th>
                                <td>{{ $xml9->mahuyen_cu_tru }}</td>
                            </tr>
                            <tr>
                                <th>Mã xã cư trú</th>
                                <td>{{ $xml9->maxa_cu_tru }}</td>
                            </tr>
                            <tr>
                                <th>Họ tên cha</th>
                                <td>{{ $xml9->ho_ten_cha }}</td>
                            </tr>
                            <tr>
                                <th>Mã thẻ tạm</th>
                                <td>{{ $xml9->ma_the_tam }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-4">
                        <table class="table table-hover">
                            <tr>
                                <th>Họ tên con</th>
                                <td>{{ $xml9->ho_ten_con }}</td>
                            </tr>
                            <tr>
                                <th>Giới tính con</th>
                                <td>{{ $xml9->gioi_tinh_con }}</td>
                            </tr>
                            <tr>
                                <th>Số con</th>
                                <td>{{ $xml9->so_con }}</td>
                            </tr>
                            <tr>
                                <th>Lần sinh</th>
                                <td>{{ $xml9->lan_sinh }}</td>
                            </tr>
                            <tr>
                                <th>Số con sống</th>
                                <td>{{ $xml9->so_con_song }}</td>
                            </tr>
                            <tr>
                                <th>Cân nặng con</th>
                                <td>{{ $xml9->can_nang_con }}</td>
                            </tr>
                            <tr>
                                <th>Ngày sinh con</th>
                                <td>{{ strtodate($xml9->ngay_sinh_con) }}</td>
                            </tr>
                            <tr>
                                <th>Nơi sinh con</th>
                                <td>{{ $xml9->noi_sinh_con }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Quá trình điều trị -->
    <div id="treatment_info9" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-hover">
                            <tr>
                                <th>Tình trạng con</th>
                                <td>{{ $xml9->tinh_trang_con }}</td>
                            </tr>
                            <tr>
                                <th>Sinh con phẫu thuật</th>
                                <td>{{ $xml9->sinhcon_phauthuat }}</td>
                            </tr>
                            <tr>
                                <th>Sinh con dưới 32 tuần</th>
                                <td>{{ $xml9->sinhcon_duoi32tuan }}</td>
                            </tr>
                            <tr>
                                <th>Ghi chú</th>
                                <td>{{ $xml9->ghi_chu }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-hover">
                            <tr>
                                <th>Người đỡ đẻ</th>
                                <td>{{ $xml9->nguoi_do_de }}</td>
                            </tr>
                            <tr>
                                <th>Người ghi phiếu</th>
                                <td>{{ $xml9->nguoi_ghi_phieu }}</td>
                            </tr>
                            <tr>
                                <th>Ngày chứng từ</th>
                                <td>{{ strtodate($xml9->ngay_ct) }}</td>
                            </tr>
                            <tr>
                                <th>Số</th>
                                <td>{{ $xml9->so }}</td>
                            </tr>
                            <tr>
                                <th>Quyển số</th>
                                <td>{{ $xml9->quyen_so }}</td>
                            </tr>
                            <tr>
                                <th>Mã TT dịch vụ</th>
                                <td>{{ $xml9->ma_ttdv }}</td>
                            </tr>
                            <tr>
                                <th>Dự phòng</th>
                                <td>{{ $xml9->du_phong }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Thông tin khác -->
    <div id="other_info9" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-hover">
                            <tr>
                                <th>Họ tên cha</th>
                                <td>{{ $xml9->ho_ten_cha }}</td>
                            </tr>
                            <tr>
                                <th>Họ tên con</th>
                                <td>{{ $xml9->ho_ten_con }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-hover">
                            <tr>
                                <th>Ghi chú</th>
                                <td>{{ $xml9->ghi_chu }}</td>
                            </tr>
                            <tr>
                                <th>Dự phòng</th>
                                <td>{{ $xml9->du_phong }}</td>
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