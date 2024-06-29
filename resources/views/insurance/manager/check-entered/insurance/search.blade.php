@push('after-styles')
<link rel="stylesheet" type="text/css" href="{{asset('/vendor/datepicker/css/bootstrap-datepicker.min.css')}}">
@endpush
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.search.block_title') }}</b>
        </div>
      
        <form type="GET" action="{{route('insurance.check-entered.insurance.search')}}">


            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="card-number">{{__('insurance.backend.labels.card-number')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="card-number" placeholder="{{__('insurance.backend.labels.card-number')}}" value="{{ $params['card-number'] }}" autofocus="">
                    </div>
                </div>
            </div>


            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="id_number">{{__('insurance.backend.labels.id_number')}}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="id_number" placeholder="{{__('insurance.backend.labels.id_number')}}" value="{{ $params['id_number'] }}">
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