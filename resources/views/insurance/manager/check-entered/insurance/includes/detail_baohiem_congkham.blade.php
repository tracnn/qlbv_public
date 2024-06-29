<!-- List baohiem_congkham-->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{__('insurance.backend.labels.medical_fee_info')}}</b>
        </div>
        <!-- /title -->
        @if($baohiem_congkham->count())
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th class="col-md-1">{{__('insurance.backend.labels.index')}}</th>
                    <th class="col-md-4">{{__('insurance.backend.medical_fee_info.medical_fee_name')}}</th>
                    <th>{{__('insurance.backend.medical_fee_info.unit')}}</th>
                    <th>{{__('insurance.backend.medical_fee_info.quality')}}</th>
                    <th>{{__('insurance.backend.medical_fee_info.price')}}</th>
                    <th>{{__('insurance.backend.medical_fee_info.total')}}</th>
                    <th>{{__('insurance.backend.medical_fee_info.clinic_name')}}</th>
                </tr>
                </thead>  
                <tbody>
                    @foreach ($baohiem_congkham as $key => $value)
                    <tr>
                        <td>{{ number_format($key+1) }}</td>
                        <td>{{ $value->dm_kieukham->tenkk }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($value->soluong) }}</td>
                        <td class="text-right">{{ number_format($value->dongia) }}</td>
                        <td class="text-right">{{ number_format($value->thanhtien) }}</td>
                        <td>{{ $value->dm_phongkham->tenpk }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <th class="text-center" colspan="5">{{__('insurance.backend.labels.sum_total')}}</th>
                    <th class="text-right">{{ number_format($baohiem_congkham->sum('thanhtien')) }}</th>
                    <th></th>
                </tfoot>              
            </table>
        </div>
        @else
            <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>
<!-- /List -->