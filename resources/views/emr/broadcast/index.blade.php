@extends('adminlte::page')

@section('title', 'Broadcast Chanel')

@section('content_header')

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<h2 id="messages" align="center" style="color: red;"></h2>
<div id="qr-code-container" align="center"></div>
<h4 id="additionalMessages" align="center"></h4>
@stop

@push('after-scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script src="https://js.pusher.com/4.1/pusher.min.js"></script>
<script>
  $(document).ready(function(){
    var pusher = new Pusher('32ba995928282d3d2fce', {
        cluster: 'ap1',
        encrypted: true
    });

    var jqxhr = @json(auth()->user()->id);

    var channel = pusher.subscribe('thu-ngan-'+jqxhr);

    channel.bind('App\\Events\\DemoPusherEvent', addMessageDemo);
  
  });

  //function add message
  function addMessageDemo(data) {
    var parsedData = JSON.parse(data.message);
    var operationType = parsedData.is_payment ? 'Cần Thanh Toán' : 'Cần Tạm Thu';
    var messageContent = `${operationType}: ${formatCurrency(parsedData.amount)}`;
    $('#messages').html(messageContent);
    // Extend messageContent with new details
    additionalMessages = `<div>Tổng Chi Phí: ${formatCurrency(parsedData.total_patient_price)}</div>`;
    additionalMessages += `<div>Đã tạm Ứng: ${formatCurrency(parsedData.tam_ung)}</div>`;
    additionalMessages += `<div>Đã Hoàn Ứng: ${formatCurrency(parsedData.hoan_ung)}</div>`;
    additionalMessages += `<div>Đã Thanh Toán: ${formatCurrency(parsedData.da_thanh_toan)}</div>`;
    $('#additionalMessages').html(additionalMessages);

    // Clear previous QR code
    $('#qr-code-container').empty();
    // Create QR code
    var qrCode = new QRCode(document.getElementById("qr-code-container"), {
      text: parsedData.qrString,
      width: 300,
      height: 300,
      colorDark : "#000000",
      colorLight : "#ffffff",
      correctLevel : QRCode.CorrectLevel.H
    });
  }
  
  function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
  }
</script>
@endpush