@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.search.block_title') }}</b>
        </div>
      
        <form type="GET" action="{{route('medreg.search')}}">
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="title">{{ __('medreg.backend.name') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="name" placeholder="{{ __('medreg.backend.name') }}" value="{{ $params['name'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="description">{{ __('medreg.backend.email') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="email" placeholder="{{ __('medreg.backend.email') }}" value="{{ $params['email'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="title">{{ __('medreg.backend.healthcaredate') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon">></div>
                                    <input class="form-control" type="date" name="healthcaredate[from]" value="{{ $params['healthcaredate']['from'] }}">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon"><</div>
                                    <input class="form-control" type="date" name="healthcaredate[to]" value="{{ $params['healthcaredate']['to'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="title">{{ __('medreg.backend.healthcaretime') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <select class="form-control healthcaretime" name="healthcaretime">
                            <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                            @foreach($healthcaretime as $key => $value)
                                <option value="{{ $key }}" {{ $params['healthcaretime'] == $key ? 'selected':'' }}>{{ $value }}</option>
                            @endforeach
                        </select>
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
<script type="text/javascript">
    $(document).ready(function() {
        $('.healthcaretime').select2({ 
            width: '100%',
            allowClear: true,
            placeholder: '{{__('medreg.backend.all')}}'
        });
    });
</script>
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
});
</script>
@endpush