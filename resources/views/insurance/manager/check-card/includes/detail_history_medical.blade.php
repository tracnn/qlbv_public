<!-- List History Medical-->
{{-- Nguon du lieu: $lichSuKcb, do InsuranceController goi ham RIENG Lskcb2025 cua cong BHXH
     (cong van 2746/BHXH-CNTT). Truoc day bang nay doc $result_insurance['dsLichSuKCB2018'] -
     khoi do nam trong ket qua tra the va nay cong luon tra ve null, nen bang mat nguon.

     Dung !empty chu khong isset: cac nhanh loi cua controller khong truyen bien nay xuong,
     va mot bang lich su thieu nguon phai hien "khong co thong tin" chu khong duoc lam trang
     ca man tra cuu the. --}}
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{__('insurance.backend.labels.history_medical_info')}}</b>
        </div>
        <!-- /title -->
        @if(!empty($lichSuKcb))
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
                    {{-- array_get cho tung o: cong da mot lan bo han mot khoi du lieu khoi
                         ket qua tra the ma khong bao truoc. Mot khoa vang mat chi duoc lam
                         trong mot o, khong duoc lam do ca man hinh. --}}
                    @foreach ($lichSuKcb as $key => $value)
                    <tr>
                        <td>{{ number_format($key+1) }}</td>
                        <td>{{ array_get($value, 'maHoSo') }}</td>
                        <td>{{ array_get($value, 'maCSKCB') }}</td>
                        <td>{{ strtodatetime(array_get($value, 'ngayVao')) }}</td>
                        <td>{{ strtodatetime(array_get($value, 'ngayRa')) }}</td>
                        <td>{{ array_get($value, 'tenBenh') }}</td>
                        <td>{{ array_get($value, 'tinhTrang') }}</td>
                        <td>{{ array_get($value, 'kqDieuTri') }}</td>
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
