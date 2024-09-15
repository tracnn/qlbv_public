<!-- File: resources/views/emr-checker/partials/treatment-detail.blade.php -->
@if($results->isNotEmpty())
    @foreach($results as $item)
        <p>
            Mã điều trị: {{ strtoupper($item->treatment_code) }};
            Họ và tên: {{ ucfirst($item->tdl_patient_name) }};
            Ngày vào: {{ strtodatetime($item->in_time) }}
        </p>
    @endforeach
@else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
@endif
