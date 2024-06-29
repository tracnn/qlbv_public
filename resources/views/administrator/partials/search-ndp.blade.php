<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.treatment_code')
                @include('partials.drug_req_type')
                @include('partials.prescription_type')
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-drug-req-type')
    @stack('after-scripts-date-range')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-load-data-button')
@endpush