<div id="menu1" class="tab-pane fade in active">
<!--     @php
        $errorDescriptions = $xml1
        ->Qd130XmlErrorResult()
        ->where('xml', 'XML1')
        ->pluck('description')
        ->implode('; ');
    @endphp
    <ul class="nav nav-tabs" @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif> -->
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#admin_info">Thông tin hành chính</a></li>
        <li><a data-toggle="tab" href="#treatment_info">Quá trình điều trị</a></li>
        <li><a data-toggle="tab" href="#cost_info">Chi phí KCB</a></li>
        <li><a data-toggle="tab" href="#other_info">Thông tin khác</a></li>
    </ul>
    <div class="tab-content">
        <!-- Thông tin hành chính -->
        <div id="admin_info" class="tab-pane fade in active">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã LK</th>
                                    <td>{{ $xml1->ma_lk }}</td>
                                </tr>
                                <tr>
                                    <th>Mã BN</th>
                                    <td>{{ $xml1->ma_bn }}</td>
                                </tr>
                                <tr>
                                    <th>Họ tên</th>
                                    <td>{{ $xml1->ho_ten }}</td>
                                </tr>
                                <tr>
                                    <th>Số CCCD</th>
                                    <td>{{ $xml1->so_cccd }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh</th>
                                    <td>{{ dob($xml1->ngay_sinh) }}</td>
                                </tr>
                                <tr>
                                    <th>Giới tính</th>
                                    <td>{{ $xml1->gioi_tinh }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Nhóm máu</th>
                                    <td>{{ $xml1->nhom_mau }}</td>
                                </tr>
                                <tr>
                                    <th>Quốc tịch</th>
                                    <td>{{ $xml1->ma_quoctich }}</td>
                                </tr>
                                <tr>
                                    <th>Dân tộc</th>
                                    <td>{{ $xml1->ma_dantoc }}</td>
                                </tr>
                                <tr>
                                    <th>Nghề nghiệp</th>
                                    <td>{{ $xml1->ma_nghe_nghiep }}</td>
                                </tr>
                                <tr>
                                    <th>Địa chỉ</th>
                                    <td>{{ $xml1->dia_chi }}</td>
                                </tr>
                                <tr>
                                    <th>Mã tỉnh cư trú</th>
                                    <td>{{ $xml1->matinh_cu_tru }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã huyện cư trú</th>
                                    <td>{{ $xml1->mahuyen_cu_tru }}</td>
                                </tr>
                                <tr>
                                    <th>Mã xã cư trú</th>
                                    <td>{{ $xml1->maxa_cu_tru }}</td>
                                </tr>
                                <tr>
                                    <th>Điện thoại</th>
                                    <td>{{ $xml1->dien_thoai }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thẻ BHYT</th>
                                    <td>{{ $xml1->ma_the_bhyt }}</td>
                                </tr>
                                <tr>
                                    <th>Nơi ĐKBĐ</th>
                                    <td>{{ $xml1->ma_dkbd }}</td>
                                </tr>
                                <tr>
                                    <th>GT thẻ từ</th>
                                    <td>{{ strtodate($xml1->gt_the_tu) }}</td>
                                </tr>
                                <tr>
                                    <th>GT thẻ đến</th>
                                    <td>{{ strtodate($xml1->gt_the_den) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quá trình điều trị -->
        <div id="treatment_info" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Ngày vào</th>
                                    <td>{{ strtodatetime($xml1->ngay_vao) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày vào nội trú</th>
                                    <td>{{ strtodatetime($xml1->ngay_vao_noi_tru) }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ra</th>
                                    <td>{{ strtodatetime($xml1->ngay_ra) }}</td>
                                </tr>
                                <tr>
                                    <th>Lý do vào viện</th>
                                    <td>{{ $xml1->ly_do_vv }}</td>
                                </tr>
                                <tr>
                                    <th>Lý do vào nội trú</th>
                                    <td>{{ $xml1->ly_do_vnt }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán vào</th>
                                    <td>{{ $xml1->chan_doan_vao }}</td>
                                </tr>
                                <tr>
                                    <th>Chẩn đoán ra</th>
                                    <td>{{ $xml1->chan_doan_rv }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã bệnh chính</th>
                                    <td>{{ $xml1->ma_benh_chinh }}</td>
                                </tr>
                                <tr>
                                    <th>Mã bệnh kèm theo</th>
                                    <td>{{ $xml1->ma_benh_kt }}</td>
                                </tr>
                                <tr>
                                    <th>Phương pháp điều trị</th>
                                    <td>{{ $xml1->pp_dieu_tri }}</td>
                                </tr>
                                <tr>
                                    <th>Kết quả điều trị</th>
                                    <td>{{ $xml1->ket_qua_dtri }}</td>
                                </tr>
                                <tr>
                                    <th>Mã loại ra viện</th>
                                    <td>{{ $xml1->ma_loai_rv }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày tái khám</th>
                                    <td>{{ $xml1->ngay_tai_kham }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Chi phí KCB -->
        <div id="cost_info" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <table class="table table-hover">
                        <tr>
                            <th>Ngày thanh toán</th>
                            <td>{{ strtodatetime($xml1->ngay_ttoan) }}</td>
                        </tr>
                        <tr>
                            <th>Tổng chi phí bệnh viện</th>
                            <td>{{ number_format($xml1->t_tongchi_bv, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Tổng chi phí bảo hiểm</th>
                            <td>{{ number_format($xml1->t_tongchi_bh, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Chi phí bệnh nhân tự trả</th>
                            <td>{{ number_format($xml1->t_bntt, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Chi phí bệnh nhân chi trả (có bảo hiểm)</th>
                            <td>{{ number_format($xml1->t_bncct, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Chi phí bảo hiểm trả</th>
                            <td>{{ number_format($xml1->t_bhtt, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Chi phí từ nguồn khác</th>
                            <td>{{ number_format($xml1->t_nguonkhac, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Chi phí bảo hiểm trả (giám định viên)</th>
                            <td>{{ number_format($xml1->t_bhtt_gdv, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <!-- Thông tin khác -->
        <div id="other_info" class="tab-pane fade">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã đối tượng KCB</th>
                                    <td>{{ $xml1->ma_doituong_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã nơi đi</th>
                                    <td>{{ $xml1->ma_noi_di }}</td>
                                </tr>
                                <tr>
                                    <th>Mã nơi đến</th>
                                    <td>{{ $xml1->ma_noi_den }}</td>
                                </tr>
                                <tr>
                                    <th>Mã tai nạn</th>
                                    <td>{{ $xml1->ma_tai_nan }}</td>
                                </tr>
                                <tr>
                                    <th>Số ngày điều trị</th>
                                    <td>{{ $xml1->so_ngay_dtri }}</td>
                                </tr>
                                <tr>
                                    <th>Mã loại KCB</th>
                                    <td>{{ $xml1->ma_loai_kcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã khoa</th>
                                    <td>{{ $xml1->ma_khoa }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-hover">
                                <tr>
                                    <th>Mã CSKCB</th>
                                    <td>{{ $xml1->ma_cskcb }}</td>
                                </tr>
                                <tr>
                                    <th>Mã khu vực</th>
                                    <td>{{ $xml1->ma_khuvuc }}</td>
                                </tr>
                                <tr>
                                    <th>Cân nặng</th>
                                    <td>{{ $xml1->can_nang }}</td>
                                </tr>
                                <tr>
                                    <th>Cân nặng con</th>
                                    <td>{{ $xml1->can_nang_con }}</td>
                                </tr>
                                <tr>
                                    <th>Năm năm liên tục</th>
                                    <td>{{ strtodate($xml1->nam_nam_lien_tuc) }}</td>
                                </tr>
                                <tr>
                                    <th>Mã hồ sơ bệnh án</th>
                                    <td>{{ $xml1->ma_hsba }}</td>
                                </tr>
                                <tr>
                                    <th>Mã thủ trưởng đơn vị</th>
                                    <td>{{ $xml1->ma_ttdv }}</td>
                                </tr>
                                <tr>
                                    <th>Dự phòng</th>
                                    <td>{{ $xml1->du_phong }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>