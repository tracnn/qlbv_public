<!-- File: resources/views/emr-checker/partials/treatment-detail.blade.php -->
@if($results->isNotEmpty())
    @foreach($results as $item)
        <p>
            <label>Mã ĐT:</label> {{ strtoupper($item->treatment_code) }};
            <label>Mã BN:</label> {{ strtoupper($item->tdl_patient_code) }};
            <label>Họ tên:</label> {{ ucfirst($item->tdl_patient_name) }};
            <label>Ngày sinh:</label> {{ dob($item->tdl_patient_dob) }};
            <label>Địa chỉ:</label> {{ $item->tdl_patient_address }};
            <label>Số ĐT:</label> {{ $item->tdl_patient_mobile ?? $item->tdl_patient_phone ?? $item->tdl_patient_relative_mobile ?? $item->tdl_patient_relative_phone }};
            <label>Diện:</label> {{ $item->treatment_type_name }};
            <label>Đối tượng:</label> {{ $item->patient_type_name }};
            <label>Mã thẻ:</label> {{ $item->tdl_hein_card_number }};
            <label>Khoa:</label> {{ $item->last_department }};
            <label>Ngày vào:</label> {{ strtodatetime($item->in_time) }};
            <label>Ngày ra:</label> {{ strtodatetime($item->out_time) }}
        </p>
    @endforeach
@else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
@endif