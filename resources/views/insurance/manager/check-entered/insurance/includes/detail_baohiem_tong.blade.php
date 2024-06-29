<!-- List baohiem_tong-->
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>{{__('insurance.backend.labels.patient_info')}}</b>
        </div>
        <!-- /title -->

        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.name')}}</th>
                    <th>{{__('insurance.backend.labels.card-number')}}</th>
                    <th>{{__('insurance.backend.labels.birthday')}}</th>
                    <th>{{__('insurance.backend.labels.date_checkup')}}</th>
                </tr>
                </thead>  
                <tbody>
                    <tr>
                        <td>{{ $baohiem_tong->hotenbn }}</td>
                        <td>{{ $baohiem_tong->sothe }}</td>
                        <td>{{ date_format(date_create(substr($baohiem_tong->bn_hc->ngaysinh,0,10)),'d/m/Y') }}</td>
                        <td>{{ date_format(date_create($baohiem_tong->ngaykham),'d/m/Y h:m') }}</td>
                    </tr>
                </tbody>              
            </table>
        </div>

        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.diagnosis_primary')}}</th>
                    <th>{{__('insurance.backend.labels.diagnosis_secondary')}}</th>
                    <th>{{__('insurance.backend.labels.insurance_pay')}}</th>
                    <th>{{__('insurance.backend.labels.patient_pay')}}</th>
                    <th>{{__('insurance.backend.labels.percent_pay')}}</th>
                </tr>
                </thead>  
                <tbody>
                    <tr>
                        <td>{{ $baohiem_tong->primary_icd->tviet }}</td>
                        <td>{{ $baohiem_tong->secondary_icd ? $baohiem_tong->secondary_icd->tviet : __('insurance.backend.labels.no_information') }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->bhtra) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->nbtra) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->phantram) }}</td>
                    </tr>
                </tbody>              
            </table>
        </div>

        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                <tr>
                    <th>{{__('insurance.backend.labels.medical_fee_total')}}</th>
                    <th>{{__('insurance.backend.labels.drug_total')}}</th>
                    <th>{{__('insurance.backend.labels.subclinical_total')}}</th>
                    <th>{{__('insurance.backend.labels.service_total')}}</th>
                    <th>{{__('insurance.backend.labels.sum_total')}}</th>
                </tr>
                </thead>  
                <tbody>
                    <tr>
                        <td class="text-right">{{ number_format($baohiem_tong->tiencong) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->tienthuoc) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->tiencls) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->tiendvkt) }}</td>
                        <td class="text-right">{{ number_format($baohiem_tong->tongcong) }}</td>
                    </tr>
                </tbody>              
            </table>
        </div>
    </div>
    <div class="panel-footer">
        <div class="row">
            <div class="col-lg-6 col-xs-6"></div>
            <div class="col-lg-6 col-xs-6">
                <div class="pull-right">
                    <a href="{{route('insurance.check-card.search',['card-number' => $baohiem_tong->sothe, 'name' => $baohiem_tong->hotenbn, 'birthday' => date_format(date_create(substr($baohiem_tong->bn_hc->ngaysinh,0,10)),'d/m/Y')])}}" title="{{__('insurance.backend.labels.check-card')}}" target="_blank"><i class="fa fa-check-square-o"></i>{{__('insurance.backend.labels.check-card')}}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /List -->