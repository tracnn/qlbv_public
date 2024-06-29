@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.search.block_title') }}</b>
        </div>
      
        <form type="GET" action="{{route('insurance-card.search')}}">

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="card-number">{{__('insurance.backend.labels.card-number')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="insurance-number" placeholder="{{__('insurance.backend.labels.card-number')}}" value="{{ $params['insurance-number'] }}" autofocus="">
                    </div>
                </div>
            </div>

            
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="name">{{__('insurance.backend.labels.name')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="name" placeholder="{{__('insurance.backend.labels.name')}}" value="{{ $params['name'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="date">{{__('insurance.backend.labels.create_date')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <div class="form-group row">
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon">></div>
                                    <input class="form-control" type="date" name="date[from]" value="{{ $params['date']['from'] }}">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="input-group input-daterange">
                                    <div class="input-group-addon"><</div>
                                    <input class="form-control" type="date" name="date[to]" value="{{ $params['date']['to'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="title">{{ __('insurance.backend.labels.order_by') }}</label>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-control order_by" name="order_by">
                            <option value="" selected hidden>{{ __('medreg.backend.all') }}</option>
                            @foreach($order_by as $key => $value)
                                <option value="{{ $key }}" {{ $params['order_by'] == $key ? 'selected':'' }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select class="form-control" name="order_type">
                            @foreach($order_type as $key => $value)
                                <option value="{{ $key }}" {{ $params['order_type'] == $key ? 'selected':'' }}>{{ $value }}</option>
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