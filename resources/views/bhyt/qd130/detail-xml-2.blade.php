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