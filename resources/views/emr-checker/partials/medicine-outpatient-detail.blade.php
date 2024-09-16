<!-- File: resources/views/emr-checker/partials/medicine-outpatient-detail.blade.php -->
@if($medicine_results->isNotEmpty())
    <h4>Đơn thuốc ngoại trú</h4>
    <div class="table table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('STT') }}</th>
                    <th>{{ __('Mã thuốc') }}</th>
                    <th>{{ __('Tên thuốc') }}</th>
                    <th>{{ __('Số lượng') }}</th>
                    <th>{{ __('Đơn vị') }}</th>
                    <th>{{ __('Hàm lượng') }}</th>
                    <th>{{ __('Trạng thái') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medicine_results as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->tdl_service_code }}</td>
                        <td>{{ $item->tdl_service_name }}</td>
                        <td class="text-right">{{ number_format($item->amount) }}</td>
                        <td>{{ $item->service_unit_name }}</td>
                        <td>{{ $item->tdl_medicine_concentra}}</td>
                        <td>{{ $item->service_req_stt_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>        
    </div>
@else
    <center>{{ __('insurance.backend.labels.no_information') }}</center>
@endif