<div id="menu5" class="tab-pane fade">
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