<!-- File: resources/views/emr-checker/partials/treatment-detail.blade.php -->
@foreach($results as $item)
    <p>
        Mã điều trị: {{ strtoupper($item->treatment_code) }};
        Họ và tên: {{ ucfirst($item->tdl_patient_name) }};
        Ngày vào: {{ strtodatetime($item->in_time) }}
    </p>
@endforeach