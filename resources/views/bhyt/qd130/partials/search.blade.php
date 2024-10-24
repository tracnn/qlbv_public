<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showDateType' => true])
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.treatment_code')
                @include('partials.patient_code')
                @include('partials.imported_by')
                @include('partials.export_qd130_xml_error')
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.treatment_type_fillter')
                @include('partials.xml_filter_status')
                @include('partials.qd130_xml_error_catalog')
                @include('partials.hein_card_filter')
                @include('partials.payment_date_filter')
                @include('partials.xml_export_status')
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-qd130-xml-error-catalog')
    @stack('after-scripts-date-range')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-imported-by')
    @stack('after-scripts-load-data-button')
@endpush