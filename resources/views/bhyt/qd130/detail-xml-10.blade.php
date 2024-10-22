<div id="menu10" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info10">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info10">Quá trình điều trị</a></li>
    </ul>
    @foreach($xml1->Qd130Xml10 as $xml10)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info10" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml10->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Số Seri</th>
                                    <td>{{ $xml10->so_seri }}</td>
                                </tr>
                                <tr>
                                    <th>Số Chứng Từ</th>
                                    <td>{{ $xml10->so_ct }}</td>
                                </tr>
                                <tr>
                                    <th>Số ngày</th>
                                    <td>{{ $xml10->so_ngay }}</td>
                                </tr>
                                <tr>
                                    <th>Đơn vị</th>
                                    <td>{{ $xml10->don_vi }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml10->chan_doan_rv }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Từ ngày</th>
                                    <td>{{ strtodate($xml10->tu_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Đến ngày</th>
                                    <td>{{ strtodate($xml10->den_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thủ trưởng đơn vị</th>
                                    <td>{{ $xml10->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Tên bác sĩ</th>
                                    <td>{{ $xml10->ten_bs }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml10->ma_bs }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml10->ngay_ct) }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml10->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info10" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Từ ngày</th>
                                    <td>{{ strtodatetime($xml10->tu_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Đến ngày</th>
                                    <td>{{ strtodatetime($xml10->den_ngay) }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml10->chan_doan_rv }}</td>
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