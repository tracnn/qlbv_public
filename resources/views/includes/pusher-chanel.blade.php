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