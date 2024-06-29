@extends('adminlte::page')

@section('title', __('insurance.backend.labels.check-entered'))

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<p>Message preview:</p>
          <ul id="messages" class="list-group"></ul>
@stop

@push('after-scripts')
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script src="{{ asset('js/socket.io.min.js') }}"></script>
<script>
  $(document).ready(function(){
    // Khởi tạo một đối tượng Pusher với app_key
    var pusher = new Pusher('32ba995928282d3d2fce', {
        cluster: 'ap1',
        encrypted: true
    });

    //Đăng ký với kênh chanel-demo-real-time mà ta đã tạo trong file DemoPusherEvent.php
    var channel = pusher.subscribe('channel-demo');

    //Bind một function addMesagePusher với sự kiện DemoPusherEvent
    channel.bind('App\\Events\\DemoPusherEvent', addMessageDemo);
  });

  //function add message
  function addMessageDemo(data) {
  	console.log(data);
    var liTag = $("<li class='list-group-item'></li>");
    liTag.html(data.message);
    $('#messages').append(liTag);
  }
</script>
<script type="text/javascript">
    var a = 'http://'+'{{ Request::getHost() }}'+':6868';
	var socket = io.connect(a);
	socket.on('testchanel',function(data){
		$('#messages').append(data);
	});
</script>
@endpush