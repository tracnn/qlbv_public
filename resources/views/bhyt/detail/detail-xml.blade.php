<!-- resources/views/bhyt/detail-xml.blade.php -->
<label>Hồ Sơ - {{ $xml1->ma_lk }}; Mã BN - {{ $xml1->ma_bn }}; 
    Họ tên - {{ $xml1->ho_ten }}; Ngày sinh - {{ $xml1->ngay_sinh }}
    Mã thẻ - {{ $xml1->ma_the }}; Nơi ĐKBĐ - {{ $xml1->ma_dkbd }}
</label>
<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#menu0">XML1</a></li>
    <li><a data-toggle="tab" href="#menu1">XML2</a></li>
    <li><a data-toggle="tab" href="#menu2">XML3</a></li>
    <li><a data-toggle="tab" href="#menu3">XML4</a></li>
    <li><a data-toggle="tab" href="#menu4">XML5</a></li>
    <li class="{{ ($xml1->check_hein_card && ($xml1->check_hein_card->ma_tracuu != '000' || 
        $xml1->check_hein_card->ma_kiemtra != '00')) ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu5">Thẻ BHYT</a>
    </li>
    <li class="{{ $xml1->xmlErrorChecks->isNotEmpty() ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu6">Lỗi XML</a>
    </li>
</ul>

<div class="tab-content">
    <div id="menu0" class="tab-pane fade in active">
        <div class="panel panel-default">
            <div class="panel panel-default">
                <div class="panel-body table-responsive">
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
                            <th>Mã thẻ</th>
                            <td>{{ $xml1->ma_the }}</td>
                        </tr>
                        <tr>
                            <th>Ngày sinh</th>
                            <td>{{ dob($xml1->ngay_sinh) }}</td>
                        </tr>
                        <tr>
                            <th>Ngày vào</th>
                            <td>{{ strtodatetime($xml1->ngay_vao) }}</td>
                        </tr>
                        <tr>
                            <th>Ngày ra</th>
                            <td>{{ strtodatetime($xml1->ngay_ra) }}</td>
                        </tr>
                        <tr>
                            <th>Ngày thanh toán</th>
                            <td>{{ strtodatetime($xml1->ngay_ttoan) }}</td>
                        </tr>                                
                    </table>
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
                        @foreach($xml1->xml2 as $value_xml2)
                        <tr>
                            <td align="right">{{ $value_xml2->stt }}</td>
                            <td>{{ $value_xml2->ma_thuoc }}</td>
                            <td>{{ $value_xml2->ten_thuoc }}</td>
                            <td>{{ $value_xml2->ham_luong }}</td>
                            <td>{{ $value_xml2->so_dang_ky }}</td>
                            <td>{{ $value_xml2->don_gia }}</td>
                            <td>{{ $value_xml2->tt_thau }}</td>
                            <td align="right">{{ number_format($value_xml2->so_luong) }}</td>
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
                        @foreach($xml1->xml3 as $value_xml3)
                        <tr>
                            <td align="right">{{ $value_xml3->stt }}</td>
                            <td>{{ $value_xml3->ma_dich_vu }}</td>
                            <td>{{ $value_xml3->ma_vat_tu }}</td>
                            <td>{{ $value_xml3->ten_dich_vu }}</td>
                            <td>{{ $value_xml3->ten_vat_tu }}</td>
                            <td>{{ config('__tech.pl6_4210')[$value_xml3->ma_nhom] }}</td>
                            <td>{{ $value_xml3->don_vi_tinh }}</td>
                            <td align="right">{{ number_format($value_xml3->t_trantt,2) }}</td> 
                            <td align="right">{{ number_format($value_xml3->so_luong) }}</td> 
                            <td align="right">{{ number_format($value_xml3->don_gia) }}</td> 
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
                        @foreach($xml1->xml4 as $value_xml4)
                        <tr>
                            <td align="right">{{ $value_xml4->stt }}</td>
                            <td>{{ $value_xml4->ma_dich_vu }}</td>
                            <td>{{ $value_xml4->ma_chi_so }}</td>
                            <td>{{ $value_xml4->ten_chi_so }}</td>
                            <td>{{ $value_xml4->gia_tri }}</td>
                            <td>{{ $value_xml4->ma_may }}</td> 
                            <td>{{ $value_xml4->mo_ta }}</td> 
                            <td>{{ $value_xml4->ket_luan }}</td>  
                            <td>{{ $value_xml4->ngay_kq }}</td>         
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
                        @foreach($xml1->xml5 as $value_xml5)
                        <tr>
                            <td>{{ $value_xml5->stt }}</td>
                            <td>{{ $value_xml5->dien_bien }}</td>
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
                @if($xml1->xmlErrorChecks->isNotEmpty())
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
                        @foreach($xml1->xmlErrorChecks as $error)
                        <tr>
                            <td>{{ $error->xmlErrorCatalog->error_name }}</td>
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