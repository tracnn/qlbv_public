@extends('adminlte::page')

@section('title', 'Quản trị hệ thống')

@section('content_header')
<h1>
    Quản trị
    <small>hệ thống</small>
</h1>
{{ Breadcrumbs::render('system.sys-man') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <ul id="messages"></ul>
    </div>
</div>

@include('includes.modal-edit-form')
@stop

@push('after-scripts')
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script>

    $(document).ready(function(){
        var pusher = new Pusher('32ba995928282d3d2fce', {
            cluster: 'ap1',
            encrypted: true
        });

        var channel = pusher.subscribe('system-manager');

        channel.bind('App\\Events\\DemoPusherEvent', addMessage);

        });

    //function add message
    function addMessage(data) {
        var liTag = $("<li></li>");
        liTag.html(data.message);
        $('#messages').append(liTag);
    }

</script>
@endpush