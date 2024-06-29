@extends('adminlte::page')

@section('title', 'Broadcast Chanel')

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<ul id="messages" class="list-group"></ul>
@stop

@push('after-scripts')
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script>
  $(document).ready(function(){
    var pusher = new Pusher('32ba995928282d3d2fce', {
        cluster: 'ap1',
        encrypted: true
    });

    var jqxhr = $.ajax({
        type: 'GET',       
        url: "{{route('system.get-user-id')}}",
        dataType: 'html',
        context: document.body,
        global: false,
        async:false,
        success: function(data) {
            return data;
        }
    }).responseText;

    var channel = pusher.subscribe('channel'+jqxhr);

    channel.bind('App\\Events\\DemoPusherEvent', addMessageDemo);
  
  });

  //function add message
  function addMessageDemo(data) {
    var liTag = $("<li class='list-group-item'></li>");
    liTag.html(data.message);
    $('#messages').html(liTag);
  }
</script>
@endpush