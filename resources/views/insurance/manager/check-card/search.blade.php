<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('insurance.backend.labels.check-card') }}</b>
        </div>

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-1">
                    <label for="qrcode">{{ __('insurance.backend.labels.qrcode') }}</label>
                </div>
                <div class="col-sm-11">
                    <input class="form-control" type="text" name="qrcode" placeholder="{{ __('insurance.backend.labels.qrcode') }}" value="{{ $params['qrcode'] }}" autofocus>
                </div>
            </div>
        </div>

        <form type="GET" action="{{route('insurance.check-card.search')}}" id="target">
            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="card-number">{{ __('insurance.backend.labels.card-number') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="card-number" placeholder="{{ __('insurance.backend.labels.card-number') }}" value="{{ old('card-number') ? old('card-number') : $params['card-number'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="name">{{ __('insurance.backend.labels.name') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="name" placeholder="{{ __('insurance.backend.labels.name') }}" value="{{ old('name') ?  old('name') : $params['name'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="birthday">{{ __('insurance.backend.labels.birthday') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="birthday" placeholder="{{ __('insurance.backend.labels.type-birthday') }}" value="{{ old('birthday') ? old('birthday') : $params['birthday'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    {{ __('insurance.backend.labels.search') }}
                </button>
            </div>              
        </form>

    </div>
</div>

@push('after-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('[name="qrcode"]').on('change', function(event) {
            event.preventDefault();
            $.ajax({
                type: "GET",
                data: {
                    'qrcode': $('[name="qrcode"]').val(),
                },
                url: "{{ route('insurance.check-card.getqrcode') }}", 
                success: function(result){
                    $('[name="card-number"]').val(result['card-number']);
                    $('[name="name"]').val(result['name']);
                    $('[name="birthday"]').val(result['birthday']);
                    $( "#target" ).submit();
                }
            });
        });
    });
</script>
@endpush