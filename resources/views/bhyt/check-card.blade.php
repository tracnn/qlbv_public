@extends('adminlte::page')

@section('title', 'Kiểm tra thẻ BHYT')

@section('content_header')
<h1>
    Kiểm tra
    <small>thẻ BHYT</small>
</h1>
{{ Breadcrumbs::render('bhyt.check-card') }}
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<div class="panel panel-default">
    <div class="panel-body">
        <label>Kết quả kiểm tra</label>
        <table class="table display table-hover" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã điều trị</th>
                    <th>Họ tên</th>
                    <th>Ngày sinh</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Mã thẻ</th>
                    <th>Mã ĐKBĐ</th>
                    <th>Giá trị từ</th>
                    <th>Giá trị đến</th>
                    <th>Ngày t.toán</th>
                    <th>Khoa kết thúc</th>
                    <th>Kết quả tra cứu</th>
                    <th>Kết quả kiểm tra</th>
                </tr>
            </thead>
            <tbody id="messages">
            </tbody>
        </table>
    </div>
</div>

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

    var channel = pusher.subscribe('KtTheBHYT'+jqxhr);

    channel.bind('App\\Events\\KtTheBHYTEvent', addMessage);

    //Function check
    $.ajax({
        url: "{{route("bhyt.process-check-card")}}",
        type: "GET",
        data: {
            ngay_ttoan_tu: "{{ Request('ngay_ttoan_tu') }}",
            ngay_ttoan_den: "{{ Request('ngay_ttoan_den') }}",
            loai_kcb: "{{ Request('loai_kcb') }}",
            ma_the: "{{ Request('ma_the') }}",
            khoa: "{{ Request('khoa') }}"
        },
    })
    .done(function(data) {
        //console.log(data);
        if (data.maKetQua == 200) {
            toastr.success('Kết nối BHXH thành công!, bắt đầu kiểm tra thẻ...');
        } else {
            toastr.error('Có lỗi trong quá trình đăng nhập BHXH...');
        }
        
    })
    .fail(function() {
        //console.log("error");
        toastr.error('Có lỗi trong quá trình đăng nhập...');
    })
    .always(function() {
        //console.log("complete");
    });
        
  
  });

  //function add message
  function addMessage(data) {
    var liTag = "<tr><td style='text-align:right'>" + data.stt + "/" + data.count  + "</td><td>" + data.ma_lk + "</td><td>" + data.hotenbn + "</td><td>" + data.ngaysinh + "</td><td>" + data.ngay_vao + "</td><td>" + data.ngay_ra + "</td><td>" + data.sothe + "</td><td>" + data.macskcb + "</td><td>" + data.thoihantu + "</td><td>" + data.thoihanden + "</td><td>" + data.ngay_ttoan + "</td><td>" + data.ma_khoa + "</td><td>" + data.message + "</td><td>" + data.message1 + "</td></tr>";
    $('#messages').append(liTag);
  }
</script>
@endpush