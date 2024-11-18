<!-- List History Medical-->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{__('insurance.backend.labels.history_medical_info')}}</b>
        </div>
        <!-- /title -->
        @if($result_insurance['dsLichSuKCB2018'])
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.index')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.maHoSo')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.maCSKCB')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.ngayVao')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.ngayRa')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.tenBenh')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.tinhTrang')}}</th>
                    <th>{{__('insurance.backend.history_medical_info.kqDieuTri')}}</th>
                </tr>
                </thead>  
                <tbody>
                    @foreach ($result_insurance['dsLichSuKCB2018'] as $key => $value)
                    <tr>
                        <td>{{ number_format($key+1) }}</td>
                        <td>{{ $value['maHoSo'] }}</td>
                        <td>{{ $value['maCSKCB'] }}</td>
                        <td>{{ strtodatetime($value['ngayVao']) }}</td>
                        <td>{{ strtodatetime($value['ngayRa']) }}</td>
                        <td>{{ $value['tenBenh'] }}</td>
                        <td>{{ $value['tinhTrang'] }}</td>
                        <td>{{ $value['kqDieuTri'] }}</td>
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