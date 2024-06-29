@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.search.block_title') }}</b>
        </div>
      
        <form type="GET" action="{{route('insurance.check-entered.report.search')}}">
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="date_checkup">{{__('insurance.backend.labels.date_checkup')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon">></div>
                                    <input class="form-control" type="date" name="date_checkup[from]" value="{{ $params['date_checkup']['from'] }}">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon"><</div>
                                    <input class="form-control" type="date" name="date_checkup[to]" value="{{ $params['date_checkup']['to'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="col-sm-6">
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="title">Tra cứu</label>
                        </div>
                        <div class="col-sm-9">
                            <select class="form-control insurance_error_code" name="insurance_error_code">
                                <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                                @foreach($insurance_error_code as $key => $value)
                                    <option value="{{ $key }}" {{ $params['insurance_error_code'] == $key ? 'selected':'' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="form-group row">
                        <div class="col-sm-3">
                            <label for="title">Kiểm tra</label>
                        </div>
                        <div class="col-sm-9">
                            <select class="form-control check_insurance_code" name="check_insurance_code">
                                <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                                @foreach($check_insurance_code as $key => $value)
                                    <option value="{{ $key }}" {{ $params['check_insurance_code'] == $key ? 'selected':'' }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    {{ __('medreg.backend.search.block_title') }}
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
    $('.insurance_error_code').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: '{{__('medreg.backend.all')}}'
    });
    $('.check_insurance_code').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: '{{__('medreg.backend.all')}}'
    });
});
</script>
@endpush