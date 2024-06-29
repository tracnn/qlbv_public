@extends('adminlte::page')

@section('title', __('insurance.backend.insurance-card.addnew'))

@section('content_header')
  <h1>
    Nhập
    <small>thẻ BHYT</small>
  </h1>
{{ Breadcrumbs::render('insurance-card.add-new') }}
@stop

@section('content')
@include('includes.message')
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('insurance.backend.insurance-card.addnew') }}</b>
        </div>

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-1">
                    <label for="qrcode">{{ __('insurance.backend.insurance-card.qrcode') }}</label>
                </div>
                <div class="col-sm-11">
                    <input class="form-control" type="text" name="qrcode" placeholder="{{ __('insurance.backend.insurance-card.qrcode') }}" value="{{ old('qrcode') }}" autofocus>
                </div>
            </div>
        </div>

        <form method="POST" action="{{route('insurance-card.store')}}" id="target">
        	{{ csrf_field() }}
            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="insurance-number">{{ __('insurance.backend.insurance-card.insurance-number') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="insurance-number" placeholder="{{ __('insurance.backend.insurance-card.insurance-number') }}" value="{{ old('insurance-number') ? old('insurance-number') : $params['insurance-number'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="name">{{ __('insurance.backend.insurance-card.name') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="name" placeholder="{{ __('insurance.backend.insurance-card.name') }}" value="{{ old('name') ? old('name') : $params['name'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="birthday">{{ __('insurance.backend.insurance-card.birthday') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="birthday" placeholder="{{ __('insurance.backend.insurance-card.type-birthday') }}" value="{{ old('birthday') ? old('birthday') : $params['birthday']}}">
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-plus"></i>
                    {{ __('insurance.backend.insurance-card.addnew') }}
                </button>
            </div>              
        </form>

    </div>
</div>
@include('insurance.manager.insurance-card.includes.result')
@include('insurance.manager.insurance-card.includes.detail_medical_records')
@stop
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
                    $('[name="insurance-number"]').val(result['card-number']);
                    $('[name="name"]').val(result['name']);
                    $('[name="birthday"]').val(result['birthday']);
                    $('#target').submit();
                }
            });
        });
    });
</script>
@endpush