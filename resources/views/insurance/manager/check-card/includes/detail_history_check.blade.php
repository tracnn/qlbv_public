<!-- List History Medical-->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>Lịch sử tra cứu</b>
        </div>
        <!-- /title -->
        @if($result_insurance['dsLichSuKT2018'])
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã User kiểm tra</th>
                    <th>Thời gian kiểm tra</th>
                    <th>Nội dung thông báo</th>
                </tr>
                </thead>  
                <tbody>
                    @foreach ($result_insurance['dsLichSuKT2018'] as $key => $value)
                    <tr>
                        <td>{{ number_format($key+1) }}</td>
                        <td>{{ $value['userKT'] }}</td>
                        <td>{{ strtodatetime($value['thoiGianKT']) }}</td>
                        <td>{{ $value['thongBao'] }}</td>
                    </tr>
                    @endforeach
                </tbody>              
            </table>
        </div>
        @else
            <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>
<!-- /List -->