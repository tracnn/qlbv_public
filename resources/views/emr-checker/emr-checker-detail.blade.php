@extends('adminlte::page')

@section('title', 'Kiểm tra chi tiết hồ sơ')

@section('content_header')
  <h1>
    Kiểm tra
    <small>hồ sơ</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('emr-checker.partials.search-emr-checker-detail')

<div class="panel panel-default">
    <div class="panel-body">
        <div id="result">
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại

    // Hàm để lấy dữ liệu bằng AJAX
    function fetchData() {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        // Bắt đầu yêu cầu AJAX mới
        currentAjaxRequest = $.ajax({
            url: '{{ route('emr-checker.emr-checker-detail-fetch-data') }}', // Đặt URL cho endpoint bạn muốn gọi
            method: 'GET',
            data: {
                treatment_code: $('#treatment_code').val() // Lấy dữ liệu từ treatment_code
            },
            success: function(response) {
                $('#result').html(response);
                // Xóa giá trị trong input và đặt focus
                $('#treatment_code').val('').focus();
            },
            error: function(xhr) {
                console.error("Yêu cầu AJAX bị lỗi:", xhr);
            }
        });
    }

    $(document).ready(function() {
        // Kiểm tra xem id="treatment_code" có giá trị không
        var treatmentCodeValue = $('#treatment_code').val();
        if (treatmentCodeValue) {
            // Nếu có giá trị, gọi hàm fetchData để lấy dữ liệu
            fetchData();
        }
    });
</script>
@endpush