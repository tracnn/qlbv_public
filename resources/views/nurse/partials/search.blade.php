<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.department_catalog')
                @include('partials.treatment_code')
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-date-range')
    @stack('after-scripts-department-catalog')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-load-data-button')
@endpush