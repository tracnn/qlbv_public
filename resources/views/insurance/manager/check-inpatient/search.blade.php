@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.info_check') }}</b>
        </div>
      
        <form type="GET" action="{{route('insurance.check-entered.inpatient.search')}}">

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="date">{{__('insurance.backend.labels.date_in')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="row input-daterange">
                            <div class="col-sm-6">
                                <input class="form-control" type="date" name="date[from]" value="{{ $params['date']['from'] }}">
                            </div>
                            <div class="col-sm-6">
                                <input class="form-control" type="date" name="date[to]" value="{{ $params['date']['to'] }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="department">{{ __('insurance.backend.labels.department') }}</label>
                    </div>
                    <div class="col-sm-9 select2">
                        <select class="form-control" name="department">
                            <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                            @foreach($dm_khoaph as $key => $value)
                                <option value="{{ $value->makhp }}" {{ $params['department'] == $value->makhp ? 'selected':'' }}>{{$value->tenkhp}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="status">{{ __('insurance.backend.labels.status') }}</label>
                    </div>
                    <div class="col-sm-9 select2">
                        <select class="form-control" name="status">
                            <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                            @foreach($trangthai_noitru as $key => $value)
                                <option value="{{ $key }}" {{ $params['status'] == $key ? 'selected':'' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="status">{{ __('insurance.backend.labels.patient_type') }}</label>
                    </div>
                    <div class="col-sm-9 select2">
                        <select class="form-control" name="patient_type">
                            <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                            @foreach($duoc_doituong as $key => $value)
                                <option value="{{ $key }}" {{ $params['patient_type'] == $key ? 'selected':'' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-eye-open"></i>
                    {{ __('medreg.backend.info_check') }}
                </button>
            </div>              
        </form>

    </div>
</div>

@push('after-scripts')
<script src="{{ asset('/vendor/datepicker/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{ asset('/vendor/datepicker/locales/bootstrap-datepicker.vi.min.js')}}"></script>
<script>
$(document).ready(function() {
    $('.input-daterange input').each(function() {
        $(this).datepicker({
            format: "yyyy-mm-dd",
            language: "vi",
            daysOfWeekHighlighted: "0,6",
            todayHighlight: true,
            autoclose: true,
        });
    });

    $('.select2 select').each(function() {
        $(this).select2({
            width: '100%',
            allowClear: true,
            placeholder: '{{__('medreg.backend.all')}}'
        });
    });
});
</script>
@endpush