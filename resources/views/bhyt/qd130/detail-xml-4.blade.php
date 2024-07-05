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