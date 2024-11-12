<div id="menu15" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info15">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info15">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#other_info15">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Qd130Xml15 as $xml15)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info15" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml15->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Mã BN</th>
                                    <td>{{ $xml15->ma_bn }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên</th>
                                    <td>{{ $xml15->ho_ten }}</td>
                                </tr>
                                <tr>
                                    <th>Số CCCD</th>
                                    <td>{{ $xml15->so_cccd }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao vị trí</th>
                                    <td>{{ $xml15->phanloai_lao_vitri }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao tiền sử</th>
                                    <td>{{ $xml15->phanloai_lao_ts }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao HIV</th>
                                    <td>{{ $xml15->phanloai_lao_hiv }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Phân loại lao VK</th>
                                    <td>{{ $xml15->phanloai_lao_vk }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao KT</th>
                                    <td>{{ $xml15->phanloai_lao_kt }}</td>
                                </tr>
                                <tr>
                                    <th>Loại điều trị lao</th>
                                    <td>{{ $xml15->loai_dtri_lao }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày bắt đầu điều trị lao</th>
                                    <td>{{ strtodate($xml15->ngaybd_dtri_lao) }}</td>
                                </tr>
                                <tr>
                                    <th>Phác đồ điều trị lao</th>
                                    <td>{{ $xml15->phacdo_dtri_lao }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày kết thúc điều trị lao</th>
                                    <td>{{ strtodate($xml15->ngaykt_dtri_lao) }}</td>
                                </tr>
                                <tr>
                                    <th>Kết quả điều trị lao</th>
                                    <td>{{ $xml15->kq_dtri_lao }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã cơ sở KCB</th>
                                    <td>{{ $xml15->ma_cskcb }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày khởi đầu HIV</th>
                                    <td>{{ strtodate($xml15->ngaykd_hiv) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày bắt đầu điều trị CTX</th>
                                    <td>{{ strtodate($xml15->ngay_bat_dau_dt_ctx) }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml15->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info15" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Phân loại lao HIV</th>
                                    <td>{{ $xml15->phanloai_lao_hiv }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao vị trí</th>
                                    <td>{{ $xml15->phanloai_lao_vitri }}</td>
                                </tr>
                                <tr>
                                    <th>Phân loại lao tiền sử</th>
                                    <td>{{ $xml15->phanloai_lao_ts }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày khởi đầu HIV</th>
                                    <td>{{ strtodate($xml15->ngaykd_hiv) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày bắt đầu điều trị CTX</th>
                                    <td>{{ strtodate($xml15->ngay_bat_dau_dt_ctx) }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml15->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info15" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml15->ghi_chu }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml15->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Người thực hiện điều trị</th>
                                    <td>{{ $xml15->nguoi_thuc_hien }}</td>
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
