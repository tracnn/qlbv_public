<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showDateType' => true])
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.treatment_code')
                @include('partials.department_catalog')
                @include('partials.patient_type')
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-date-range')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-department-catalog')
    @stack('after-scripts-patient-type')
    @stack('after-scripts-load-data-button')
@endpush