<div class="panel panel-default">
    <div class="panel-body">
    	<label>Thông báo:</label>
        <ul id="messages" class="list-group"></ul>
    </div>
</div>

@push('after-scripts')
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script>
  $(document).ready(function(){
    var pusher = new Pusher('32ba995928282d3d2fce', {
        cluster: 'ap1',
        encrypted: true
    });


    var channel = pusher.subscribe($('input[name=_token]').val());

    channel.bind('App\\Events\\DemoPusherEvent', addMessage);
  
  });

  //function add message
  function addMessage(data) {
    var liTag = $("<li class='list-group-item'></li>");
    liTag.html(data.message);
    $('#messages').html(liTag);
  }
</script>
@endpush