@if($model)
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.search.block_title') }}</b>
        </div>

        <form type="GET" action="{{ route('category.search', Request('category')) }}">
            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="title">{{ __('medreg.backend.category.code') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="code" placeholder="{{ __('medreg.backend.category.code') }}" value="{{ $params['code'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="description">{{ __('medreg.backend.category.name') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="name" placeholder="{{ __('medreg.backend.category.name') }}" value="{{ $params['name'] }}">
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
@endif