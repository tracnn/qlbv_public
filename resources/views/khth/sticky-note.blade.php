@extends('adminlte::page')

@section('title', 'Nhắc việc')

@section('content_header')
  <h1>
    KHTH
    <small>Nhắc việc</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <form method="POST" id="myform" action="{{route('khth.save-sticky-note')}}">
            {{ csrf_field() }}

            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="form-group">
                        <textarea class="form-control ckeditor" name="content" id="content" required>{!! $note->content !!}</textarea>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="form-group">    
                        <button class="btn btn-info">
                        <i class="glyphicon glyphicon-level-up"></i>
                            Lưu
                        </button>
                    </div>
                </div>
            </div>  

        </form>
    </div>
</div>

@stop

@push('after-scripts')

@endpush