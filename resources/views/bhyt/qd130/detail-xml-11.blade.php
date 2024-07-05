<div id="menu11" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info11">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info11">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#other_info11">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Qd130Xml11 as $xml11)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info11" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml11->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Số chứng từ</th>
                                    <td>{{ $xml11->so_ct }}</td>
                                </tr>
                                <tr>
                                    <th>Số seri</th>
                                    <td>{{ $xml11->so_seri }}</td>
                                </tr>
                                <tr>
                                    <th>Số KCB</th>
                                    <td>{{ $xml11->so_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Đơn vị</th>
                                    <td>{{ $xml11->don_vi }}</td>
                                </tr>
                                <tr>
                                    <th>Mã BHXH</th>
                                    <td>{{ $xml11->ma_bhxh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ BHYT</th>
                                    <td>{{ $xml11->ma_the_bhyt }}</td>
                                </tr>
                                <tr>
                                    <th>Mã đình chỉ thai</th>
                                    <td>{{ $xml11->ma_dinh_chi_thai }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Nguyên nhân đình chỉ</th>
                                    <td>{{ $xml11->nguyennhan_dinhchi }}</td>
                                </tr>
                                <tr>
                                    <th>Tuổi thai</th>
                                    <td>{{ $xml11->tuoi_thai }}</td>
                                </tr>
                                <tr>
                                    <th>Số ngày nghỉ</th>
                                    <td>{{ $xml11->so_ngay_nghi }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml11->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml11->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Từ ngày</th>
                                    <td>{{ strtodate($xml11->tu_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Đến ngày</th>
                                    <td>{{ strtodate($xml11->den_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml11->ho_ten_cha }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml11->ho_ten_me }}</td>
                                </tr>
                                <tr>
                                    <th>Mã TT dịch vụ</th>
                                    <td>{{ $xml11->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml11->ma_bs }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml11->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ tạm</th>
                                    <td>{{ $xml11->ma_the_tam }}</td>
                                </tr>
                                <tr>
                                    <th>Mẫu số</th>
                                    <td>{{ $xml11->mau_so }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info11" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml11->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml11->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Mã đình chỉ thai</th>
                                    <td>{{ $xml11->ma_dinh_chi_thai }}</td>
                                </tr>
                                <tr>
                                    <th>Nguyên nhân đình chỉ</th>
                                    <td>{{ $xml11->nguyennhan_dinhchi }}</td>
                                </tr>
                                <tr>
                                    <th>Tuổi thai</th>
                                    <td>{{ $xml11->tuoi_thai }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Số ngày nghỉ</th>
                                    <td>{{ $xml11->so_ngay_nghi }}</td>
                                </tr>
                                <tr>
                                    <th>Từ ngày</th>
                                    <td>{{ strtodate($xml11->tu_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Đến ngày</th>
                                    <td>{{ strtodate($xml11->den_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên cha</th>
                                    <td>{{ $xml11->ho_ten_cha }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên mẹ</th>
                                    <td>{{ $xml11->ho_ten_me }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info11" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã TT dịch vụ</th>
                                    <td>{{ $xml11->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml11->ma_bs }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml11->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ tạm</th>
                                    <td>{{ $xml11->ma_the_tam }}</td>
                                </tr>
                                <tr>
                                    <th>Mẫu số</th>
                                    <td>{{ $xml11->mau_so }}</td>
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