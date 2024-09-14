<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.treatment_code')
            </div>
        </div>
        @include('partials.load_data_button_without_date_range')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-load-data-button-without-date-range')
@endpush