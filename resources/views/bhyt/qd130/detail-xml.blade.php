<!-- resources/views/bhyt/detail-xml.blade.php -->
<label>Hồ Sơ - {{ $xml1->ma_lk }}; Mã BN - {{ $xml1->ma_bn }}; 
    Họ tên - {{ $xml1->ho_ten }}; Ngày sinh - {{ dob($xml1->ngay_sinh) }}
    Mã thẻ - {{ $xml1->ma_the_bhyt }}; Nơi ĐKBĐ - {{ $xml1->ma_dkbd }}
</label>
<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#menu0">XML1</a></li>
    @if($xml1->Qd130Xml2->isNotEmpty())
    <li><a data-toggle="tab" href="#menu1">XML2</a></li>
    @endif
    @if($xml1->Qd130Xml3->isNotEmpty())
    <li><a data-toggle="tab" href="#menu2">XML3</a></li>
    @endif
    @if($xml1->Qd130Xml4->isNotEmpty())
    <li><a data-toggle="tab" href="#menu3">XML4</a></li>
    @endif
    @if($xml1->Qd130Xml5->isNotEmpty())
    <li><a data-toggle="tab" href="#menu4">XML5</a></li>
    @endif
    <li class="{{ ($xml1->check_hein_card && ($xml1->check_hein_card->ma_tracuu != '000' || 
        $xml1->check_hein_card->ma_kiemtra != '00')) ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu5">Thẻ BHYT</a>
    </li>
    @if($xml1->Qd130XmlErrorResult->isNotEmpty())
    <li class="{{ $xml1->Qd130XmlErrorResult->isNotEmpty() ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu6">Lỗi XML</a>
    </li>
    @endif
</ul>

<div class="tab-content">
    <div id="menu0" class="tab-pane fade in active">
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
                                        <th>Mã TT dịch vụ</th>
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
    <div id="menu1" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <table id="thuocvt" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã thuốc</th>
                            <th>Tên thuốc</th>
                            <th>Hàm lượng</th>
                            <th>Số đăng ký</th>
                            <th>Giá</th>
                            <th>TT thầu</th>
                            <th>SL</th>
                            <th>Khoa</th>
                            <th>Bác sĩ</th>
                            <th>Mã bệnh</th>
                            <th>Ngày YL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($xml1->Qd130Xml2 as $value_xml2)
                        <tr>
                            <td align="right">{{ $value_xml2->stt }}</td>
                            <td>{{ $value_xml2->ma_thuoc }}</td>
                            <td>{{ $value_xml2->ten_thuoc }}</td>
                            <td>{{ $value_xml2->ham_luong }}</td>
                            <td>{{ $value_xml2->so_dang_ky }}</td>
                            <td align="right">{{ number_format($value_xml2->don_gia, 2) ?: '' }}</td>
                            <td>{{ $value_xml2->tt_thau }}</td>
                            <td align="right">{{ number_format($value_xml2->so_luong, 2) ?: '' }}</td>
                            <td>{{ $value_xml2->ma_khoa }}</td>
                            <td>{{ $value_xml2->ma_bac_si }}</td>
                            <td>{{ $value_xml2->ma_benh }}</td>
                            <td>{{ strtodatetime($value_xml2->ngay_yl) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="menu2" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <table id="dvkt" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã DV</th>
                            <th>Mã VT</th>
                            <th>Tên DV</th>
                            <th>Tên VT</th>
                            <th>Nhóm</th>
                            <th>ĐVT</th>
                            <th>Trần BHTT</th>
                            <th>SL</th>
                            <th>Giá</th>
                            <th>TT thầu</th>
                            <th>Khoa</th>
                            <th>Bác sĩ</th>
                            <th>Mã bệnh</th>
                            <th>Ngày YL</th>
                            <th>Ngày KQ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($xml1->Qd130Xml3 as $value_xml3)
                        <tr>
                            <td align="right">{{ $value_xml3->stt }}</td>
                            <td>{{ $value_xml3->ma_dich_vu }}</td>
                            <td>{{ $value_xml3->ma_vat_tu }}</td>
                            <td>{{ $value_xml3->ten_dich_vu }}</td>
                            <td>{{ $value_xml3->ten_vat_tu }}</td>
                            <td>{{ config('__tech.pl6_4210')[$value_xml3->ma_nhom] }}</td>
                            <td>{{ $value_xml3->don_vi_tinh }}</td>
                            <td align="right">{{ $value_xml3->t_trantt ? number_format($value_xml3->t_trantt, 2) : '' }}</td> 
                            <td align="right">{{ number_format($value_xml3->so_luong, 2) ?: '' }}</td> 
                            <td align="right">{{ number_format($value_xml3->don_gia_bh, 2) ?: '' }}</td> 
                            <td>{{ $value_xml3->tt_thau }}</td> 
                            <td>{{ $value_xml3->ma_khoa }}</td> 
                            <td>{{ $value_xml3->ma_bac_si }}</td> 
                            <td>{{ $value_xml3->ma_benh }}</td> 
                            <td>{{ strtodatetime($value_xml3->ngay_yl) }}</td>     
                            <td>{{ strtodatetime($value_xml3->ngay_kq) }}</td>          
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="menu3" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <table id="cls" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã DV</th>
                            <th>Mã chỉ số</th>
                            <th>Tên chỉ số</th>
                            <th>Giá trị</th>
                            <th>Mã máy</th>
                            <th>Mô tả</th>
                            <th>Kết luận</th>
                            <th>Ngày KQ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($xml1->Qd130Xml4 as $value_xml4)
                        <tr>
                            <td align="right">{{ $value_xml4->stt }}</td>
                            <td>{{ $value_xml4->ma_dich_vu }}</td>
                            <td>{{ $value_xml4->ma_chi_so }}</td>
                            <td>{{ $value_xml4->ten_chi_so }}</td>
                            <td>{{ $value_xml4->gia_tri }}</td>
                            <td>{{ $value_xml4->ma_may }}</td> 
                            <td>{{ $value_xml4->mo_ta }}</td> 
                            <td>{{ $value_xml4->ket_luan }}</td>  
                            <td>{{ strtodatetime($value_xml4->ngay_kq) }}</td>         
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div id="menu4" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                <table id="dienbien" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Diễn biến</th>
                            <th>Hội chẩn</th>
                            <th>Phẫu thuật</th>
                            <th>Ngày YL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($xml1->Qd130Xml5 as $value_xml5)
                        <tr>
                            <td>{{ $value_xml5->stt }}</td>
                            <td>{{ $value_xml5->dien_bien_ls }}</td>
                            <td>{{ $value_xml5->hoi_chan }}</td>
                            <td>{{ $value_xml5->phau_thuat }}</td>
                            <td>{{ strtodatetime($value_xml5->ngay_yl) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
        <div id="menu5" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                @if($xml1->check_hein_card)
                <table id="checkHeinCard" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Mã Tra Cứu</th>
                            <th>Mã Kiểm Tra</th>
                            <th>Ghi Chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ config('__tech.insurance_error_code')[$xml1->check_hein_card->ma_tracuu] }}</td>
                            <td>{{ config('__tech.check_insurance_code')[$xml1->check_hein_card->ma_kiemtra] }}</td>
                            <td>{{ $xml1->check_hein_card->ghi_chu }}</td>
                        </tr>
                    </tbody>
                </table>
                @else
                <p>Không có dữ liệu Check Hein Card.</p>
                @endif
            </div>
        </div>
    </div>
    <div id="menu6" class="tab-pane fade">
        <div class="panel panel-default">
            <div class="panel-body table-responsive">
                @if($xml1->Qd130XmlErrorResult->isNotEmpty())
                <table id="xmlErrorChecks" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Error Code</th>
                            <th>XML</th>
                            <th>STT</th>
                            <th>Ngày y lệnh</th>
                            <th>Ngày kết quả</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($xml1->Qd130XmlErrorResult as $error)
                        <tr>
                            <td>{{ $error->Qd130XmlErrorCatalog->error_name }}</td>
                            <td>{{ $error->xml }}</td>
                            <td>{{ $error->stt }}</td>
                            <td>{{ strtodatetime($error->ngay_yl) }}</td>
                            <td>{{ strtodatetime($error->ngay_kq) }}</td>
                            <td>{{ $error->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p>Không có lỗi XML nào.</p>
                @endif
            </div>
        </div>
    </div>
</div>