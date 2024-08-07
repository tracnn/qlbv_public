<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-date-range')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-load-data-button')
@endpush