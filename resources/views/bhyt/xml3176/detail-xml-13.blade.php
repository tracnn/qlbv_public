<div id="menu13" class="tab-pane fade">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info13">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info1">Quá trình điều trị 1</a></li>
        <li><a data-toggle="tab" href="#treatment_info2">Quá trình điều trị 2</a></li>
        <li><a data-toggle="tab" href="#other_info13">Thông tin khác</a></li>
    </ul>
    @foreach($xml1->Xml3176Xml13 as $xml13)
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info13" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml13->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Số hồ sơ</th>
                                    <td>{{ $xml13->so_hoso }}</td>
                                </tr>
                                <tr>
                                    <th>Số chuyển tuyến</th>
                                    <td>{{ $xml13->so_chuyentuyen }}</td>
                                </tr>
                                <tr>
                                    <th>Giấy chuyển tuyến</th>
                                    <td>{{ $xml13->giay_chuyen_tuyen }}</td>
                                </tr>
                                <tr>
                                    <th>Mã CSKCB</th>
                                    <td>{{ $xml13->ma_cskcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã nơi đi</th>
                                    <td>{{ $xml13->ma_noi_di }}</td>
                                </tr>
                                <tr>
                                    <th>Mã nơi đến</th>
                                    <td>{{ $xml13->ma_noi_den }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Họ tên</th>
                                    <td>{{ $xml13->ho_ten }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh</th>
                                    <td>{{ strtodate($xml13->ngay_sinh) }}</td>
                                </tr>
                                <tr>
                                    <th>Giới tính</th>
                                    <td>{{ $xml13->gioi_tinh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã quốc tịch</th>
                                    <td>{{ $xml13->ma_quoctich }}</td>
                                </tr>
                                <tr>
                                    <th>Mã dân tộc</th>
                                    <td>{{ $xml13->ma_dantoc }}</td>
                                </tr>
                                <tr>
                                    <th>Mã nghề nghiệp</th>
                                    <td>{{ $xml13->ma_nghe_nghiep }}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ</th>
                                    <td>{{ $xml13->dia_chi }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã thẻ BHYT</th>
                                    <td>{{ $xml13->ma_the_bhyt }}</td>
                                </tr>
                                <tr>
                                    <th>GT thẻ đến</th>
                                    <td>{{ strtodate($xml13->gt_the_den) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodate($xml13->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào nội trú</th>
                                    <td>{{ strtodate($xml13->ngay_vao_noi_tru) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodate($xml13->ngay_ra) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quá trình điều trị 1 -->
        <div id="treatment_info1" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Dấu hiệu lâm sàng</th>
                                    <td>{{ $xml13->dau_hieu_ls }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra viện</th>
                                    <td>{{ $xml13->chan_doan_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml13->pp_dieutri }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh chính</th>
                                    <td>{{ $xml13->ma_benh_chinh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh kèm theo</th>
                                    <td>{{ $xml13->ma_benh_kt }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Quá trình bệnh lý</th>
                                    <td>{{ $xml13->qt_benhly }}</td>
                                </tr>
                                <tr>
                                    <th>Tóm tắt kết quả</th>
                                    <td>{{ $xml13->tomtat_kq }}</td>
                                </tr>
                                <tr>
                                    <th>Hướng điều trị</th>
                                    <td>{{ $xml13->huong_dieu_tri }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml13->ngay_ct) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quá trình điều trị 2 -->
        <div id="treatment_info2" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Tên dịch vụ</th>
                                    <td>{{ $xml13->ten_dich_vu }}</td>
                                </tr>
                                <tr>
                                    <th>Tên thuốc</th>
                                    <td>{{ $xml13->ten_thuoc }}</td>
                                </tr>
                                <tr>
                                    <th>Mã loại ra viện</th>
                                    <td>{{ $xml13->ma_loai_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Mã lý do chuyển tuyến</th>
                                    <td>{{ $xml13->ma_lydo_ct }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Phương tiện vận chuyển</th>
                                    <td>{{ $xml13->phuongtien_vc }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên người hỗ trợ</th>
                                    <td>{{ $xml13->hoten_nguoi_ht }}</td>
                                </tr>
                                <tr>
                                    <th>Chức danh người hỗ trợ</th>
                                    <td>{{ $xml13->chucdanh_nguoi_ht }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bác sĩ</th>
                                    <td>{{ $xml13->ma_bac_si }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thủ trưởng đơn vị</th>
                                    <td>{{ $xml13->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml13->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thông tin khác -->
        <div id="other_info13" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã thẻ tạm</th>
                                    <td>{{ $xml13->ma_the_tam }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml13->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày chứng từ</th>
                                    <td>{{ strtodate($xml13->ngay_ct) }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên người hỗ trợ</th>
                                    <td>{{ $xml13->hoten_nguoi_ht }}</td>
                                </tr>
                                <tr>
                                    <th>Chức danh người hỗ trợ</th>
                                    <td>{{ $xml13->chucdanh_nguoi_ht }}</td>
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