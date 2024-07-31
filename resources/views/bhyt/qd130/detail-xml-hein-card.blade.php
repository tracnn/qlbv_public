<div id="menu-hein-card" class="tab-pane fade">
    <div class="panel panel-default">
        <div class="panel-body table-responsive">
            @if($xml1->check_hein_card)
            <table id="checkHeinCard" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Mã Tra Cứu</th>
                        <th>Mã Kiểm Tra</th>
                        <th>Ghi Chú</th>
                        <th>Thời Gian</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ config('__tech.insurance_error_code')[$xml1->check_hein_card->ma_tracuu] }}</td>
                        <td>{{ config('__tech.check_insurance_code')[$xml1->check_hein_card->ma_kiemtra] }}</td>
                        <td>{{ $xml1->check_hein_card->ghi_chu }}</td>
                        <td>{{ $xml1->check_hein_card->created_at }}</td>
                    </tr>
                </tbody>
            </table>
            @else
            <p>Không có dữ liệu Check Hein Card.</p>
            @endif
        </div>
    </div>
</div>