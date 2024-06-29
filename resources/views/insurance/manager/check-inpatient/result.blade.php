<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('medreg.backend.info_check') }}</b>
        </div>
        @if(isset($bn_nhapkhoa) && $bn_nhapkhoa->count())
        <div class="table-responsive">
            <table id="example" class="table table-hover" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Họ tên</th>
                    <th>Số thẻ</th>
                    <th>Ngày sinh</th>
                    <th>Mã CSKCB</th>
                    <th>Giá trị từ</th>
                    <th>Giá trị đến</th>
                    <th>Ngày nhập khoa</th>
                    <th>Tên khoa</th>
                    <th>Trạng thái</th>
                    <th>Đối tượng</th>
                    <th>Kết quả tra cứu</th>
                    <th>Kết quả kiểm tra</th>
                </tr>
             </thead>
            <tbody id="messages">

            </tbody>
            </table>
        </div>
        @else
        <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif
    </div>
</div>


@push('after-scripts')
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script>
  $(document).ready(function(){
    // Khởi tạo một đối tượng Pusher với app_key
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

    //console.log(jqxhr); 
    //Đăng ký với kênh chanel-demo-real-time mà ta đã tạo trong file DemoPusherEvent.php
    var channel = pusher.subscribe('CheckInpatient'+jqxhr);

    //Bind một function addMesagePusher với sự kiện DemoPusherEvent
    channel.bind('App\\Events\\CheckInpatientEvent', addMessageDemo);
  });

  //function add message
  function addMessageDemo(data) {
    //var trTag = $("<tr></tr>");
    var trTag = "<tr><td class='text-right'>" + data.stt + "/" + data.count + "</td><td>" + data.hotenbn + "</td><td>" + data.sothe + "</td><td>" + data.ngaysinh + "</td><td>" + data.macskcb + "</td><td>" + data.thoihantu + "</td><td>" + data.thoihanden + "</td><td>" + data.ngay + "</td><td>" + data.tenkhp + "</td><td>" + data.trangthai + "</td><td>" + data.doituong + "</td><td>" + data.message + "</td><td>" + data.message1 + "</td></tr>";
    $('#messages').append(trTag);
  }
</script>
@endpush