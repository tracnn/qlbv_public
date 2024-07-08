<div id="menu7" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info7">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info7">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#other_info7">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Qd130Xml7 as $xml7)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info7" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml7->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Số lưu trữ</th>
                                    <td>{{ $xml7->so_luu_tru }}</td>
                                </tr>
                                <tr>
                                    <th>Mã y tế</th>
                                    <td>{{ $xml7->ma_yte }}</td>
                                </tr>
                                <tr>
                                    <th>Mã khoa RV</th>
                                    <td>{{ $xml7->ma_khoa_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodate($xml7->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodate($xml7->ngay_ra) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã đình chỉ thai</th>
                                    <td>{{ $xml7->ma_dinh_chi_thai }}</td>
                                </tr>
                                <tr>
                                    <th>Nguyên nhân đình chỉ</th>
                                    <td>{{ $xml7->nguyennhan_dinhchi }}</td>
                                </tr>
                                <tr>
                                    <th>Thời gian đình chỉ</th>
                                    <td>{{ $xml7->thoigian_dinhchi }}</td>
                                </tr>
                                <tr>
                                    <th>Tuổi thai</th>
                                    <td>{{ $xml7->tuoi_thai }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml7->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml7->pp_dieutri }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml7->ghi_chu }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thủ trưởng đơn vị</th>
                                    <td>{{ $xml7->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml7->ma_bs }}</td>
                                </tr>
                                <tr>
                                    <th>Tên bác sĩ</th>
                                    <td>{{ $xml7->ten_bs }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml7->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã cha</th>
                                    <td>{{ $xml7->ma_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Mã mẹ</th>
                                    <td>{{ $xml7->ma_me }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ tạm</th>
                                    <td>{{ $xml7->ma_the_tam }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml7->ho_ten_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml7->ho_ten_me }}</td>
                                </tr>
                                <tr>
                                    <th>Số ngày nghỉ</th>
                                    <td>{{ $xml7->so_ngay_nghi }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú từ ngày</th>
                                    <td>{{ strtodate($xml7->ngoaitru_tungay) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú đến ngày</th>
                                    <td>{{ strtodate($xml7->ngoaitru_denngay) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info7" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodatetime($xml7->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodatetime($xml7->ngay_ra) }}</td>
                                </tr>
                                <tr>
                                    <th>Nguyên nhân đình chỉ</th>
                                    <td>{{ $xml7->nguyennhan_dinhchi }}</td>
                                </tr>
                                <tr>
                                    <th>Thời gian đình chỉ</th>
                                    <td>{{ $xml7->thoigian_dinhchi }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml7->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml7->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Ghi chú</th>
                                    <td>{{ $xml7->ghi_chu }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info7" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã cha</th>
                                    <td>{{ $xml7->ma_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Mã mẹ</th>
                                    <td>{{ $xml7->ma_me }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml7->ho_ten_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml7->ho_ten_me }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Số ngày nghỉ</th>
                                    <td>{{ $xml7->so_ngay_nghi }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú từ ngày</th>
                                    <td>{{ strtodate($xml7->ngoaitru_tungay) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngoại trú đến ngày</th>
                                    <td>{{ strtodate($xml7->ngoaitru_denngay) }}</td>
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