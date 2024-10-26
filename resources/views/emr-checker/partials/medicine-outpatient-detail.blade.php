<!-- File: resources/views/emr-checker/partials/medicine-outpatient-detail.blade.php -->
@if($medicine_results->isNotEmpty())
    <h4>Đơn thuốc ngoại trú
    <button id="print-prescription" class="btn btn-primary" onclick="printPartialContent('.table-print-section');">
        {{ __('In Đơn Thuốc') }}
    </button></h4>
    <div class="table table-responsive table-print-section">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('STT') }}</th>
                    <th>{{ __('Mã thuốc') }}</th>
                    <th>{{ __('Tên thuốc') }}</th>
                    <th>{{ __('Số lượng') }}</th>
                    <th>{{ __('Đơn vị') }}</th>
                    <th>{{ __('Hàm lượng') }}</th>
                    <th>{{ __('Hướng dẫn') }}</th>
                    <th>{{ __('Trạng thái') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medicine_results as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->tdl_service_code }}</td>
                        <td><strong>{{ $item->tdl_service_name }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($item->amount) }}</strong></td>
                        <td>{{ $item->service_unit_name }}</td>
                        <td>{{ $item->tdl_medicine_concentra }}</td>
                        <td>{{ $item->tutorial }}</td>
                        <td>{{ $item->service_req_stt_name }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>        
    </div>

    <script>
        function printPartialContent(selector) {
            $(selector).printThis({
                importCSS: true, // Sử dụng CSS hiện tại
                importStyle: true, // Sử dụng inline style hiện tại
                loadCSS: "{{ asset('css/app.css') }}" // Đảm bảo liên kết đúng CSS của bạn nếu cần
            });
        }
    </script>

@else
    <center>{{ __('insurance.backend.labels.no_information') }}</center>
@endif