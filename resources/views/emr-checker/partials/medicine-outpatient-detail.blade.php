<!-- File: resources/views/emr-checker/partials/medicine-outpatient-detail.blade.php -->
@if($medicine_results->isNotEmpty())
    <style type="text/css">
    @media print {
        .no-print { 
            display: none !important; 
        }

        .print-only {
            display: block !important; /* Hiển thị phần này chỉ khi in */
        }

        .table-print-section th, .table-print-section td {
            word-wrap: break-word; /* Đảm bảo chữ được xuống dòng nếu cần */
            white-space: normal; /* Cho phép xuống dòng */
        }
    }

    .print-only {
        display: none; /* Ẩn phần này khi không ở chế độ in */
    }
    </style>

    <h4>Đơn thuốc ngoại trú
    <button id="print-prescription" class="btn btn-primary" onclick="printPartialContent('.table-print-section');">
        {{ __('In Đơn Thuốc') }}
    </button></h4>

    <div class="table table-responsive table-print-section">
        <div class="print-only">
            <div class="patient-info">
                @if($results->isNotEmpty())
                    @foreach($results as $item)
                        <p>
                            <label>Mã ĐT:</label> {{ strtoupper($item->treatment_code) }};
                            <label>Mã BN:</label> {{ strtoupper($item->tdl_patient_code) }};
                            <label>Họ tên:</label> {{ ucfirst($item->tdl_patient_name) }};
                            <label>Ngày sinh:</label> {{ dob($item->tdl_patient_dob) }};
                            <label>Số ĐT:</label> {{ $item->tdl_patient_mobile ?? $item->tdl_patient_phone ?? $item->tdl_patient_relative_mobile ?? $item->tdl_patient_relative_phone }};
                            <label>Mã thẻ:</label> {{ $item->tdl_hein_card_number }};
                            <label>Ngày vào:</label> {{ strtodatetime($item->in_time) }};
                            <label>Ngày ra:</label> {{ strtodatetime($item->out_time) }}
                        </p>
                    @endforeach
                @else
                    <center>{{__('insurance.backend.labels.no_information')}}</center>
                @endif
            </div>
        </div>
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('STT') }}</th>
                    <th class="no-print">{{ __('Mã thuốc') }}</th>
                    <th>{{ __('Tên thuốc') }}</th>
                    <th>{{ __('Số lượng') }}</th>
                    <th>{{ __('Đơn vị') }}</th>
                    <th>{{ __('Hàm lượng') }}</th>
                    <th>{{ __('Hướng dẫn') }}</th>
                    <th class="no-print">{{ __('Trạng thái') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($medicine_results as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="no-print">{{ $item->tdl_service_code }}</td>
                        <td><strong>{{ $item->tdl_service_name }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($item->amount) }}</strong></td>
                        <td>{{ $item->service_unit_name }}</td>
                        <td>{{ $item->tdl_medicine_concentra }}</td>
                        <td>{{ $item->tutorial }}</td>
                        <td class="no-print">{{ $item->service_req_stt_name }}</td>
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