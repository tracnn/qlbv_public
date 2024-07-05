<div id="menu-xml-errors" class="tab-pane fade">
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
