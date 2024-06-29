@extends('adminlte::page')

@section('title', 'Doanh thu')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê Doanh thu</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <form method="GET" action="">
            <div class="col-sm-12">
                <div class="form-group row">
                    <!-- Chọn kiểu thời gian -->
                    <div class="col-sm-2">
                        <div class="form-group row">       
                            <label for="date_type">Lọc theo</label>
                            <select class="form-control" id="date_type">
                                <option value="date_intruction">Ngày chỉ định</option>
                                <option value="date_in">Ngày vào</option>
                                <option value="date_out">Ngày ra</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group row">       
                            <label for="kieu_thong_ke">Thống kê theo</label>
                            <select class="form-control" id="kieu_thong_ke">
                                <option value="tuan">Tuần</option>
                                <option value="thang">Tháng</option>
                                <option value="nam">Năm</option>
                                <option value="tuy_bien">Tùy biến</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-1">
                        <div class="form-group row">
                            <label for="kieu_thong_ke">Tăng/Giảm</label><br/>
                            <input type="button" class="btn btn-info" id="decrease" value="&lt;">
                            <input type="button" class="btn btn-info" id="increase" value="&gt;">
                        </div>
                    </div>
                    <!-- Chọn khoảng thời gian -->
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <label for="tu_ngay">Từ</label>
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="tu_ngay">
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <label for="den_ngay">Đến</label>
                            <div class="input-daterange">
                                <input class="form-control" type="date" id="den_ngay">
                            </div>
                        </div>
                    </div>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary form-control" id="load_data_button">Tải dữ liệu...</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <!-- Left col -->
    <div class="panel-body no-padding">
        <!-- Custom tabs (Charts with tabs)-->
        <!-- Tabs within a box -->
        <img class="center-block" id="loading-image" src="../images/ajax-loader.gif" style="display: none; padding: 10px;" />
        <!-- Morris chart - Sales -->
        <img id="inpatientChart" src="" style="width: 100%;height: auto;">
    </div>
</div>


@stop

@push('after-scripts')
<script type="text/javascript">
var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại

function fetchData(){
    // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
    if (currentAjaxRequest != null) {
        currentAjaxRequest.abort();
    }

    $('#loading-image').show();
    var date_type = $('#date_type').val();
    var url = ''; // Khởi tạo URL rỗng
    var data = {}; // Khởi tạo dữ liệu gửi đi rỗng

    // Xác định URL và dữ liệu gửi đi dựa trên giá trị của date_type

    url = '{{ route("khth.revenue-processing") }}'; // Giả sử đây là endpoint cho Đang điều trị
    // Không cần dữ liệu gửi đi trong trường hợp này
    data = {
        'tu_ngay': $('#tu_ngay').val(),
        'den_ngay': $('#den_ngay').val(),
        'date_type': $('#date_type').val(),
        'kieu_thong_ke': $('#kieu_thong_ke').val()
    };

    // Thực hiện gọi AJAX
    currentAjaxRequest = $.ajax({
      url: url,
      type: "GET",
      dataType: 'json',
      data: data,
    })
    .done(function(data) {
        // Cập nhật src của ảnh với dữ liệu nhận được
        document.getElementById('inpatientChart').src = 'data:image/png;base64,' + data.image;
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      // Nếu yêu cầu thất bại, hiển thị lỗi trong console
      console.log(textStatus + ': ' + errorThrown);
    })
    .always(function() {
        $('#loading-image').hide();
        currentAjaxRequest = null; // Đặt lại biến khi yêu cầu hoàn thành hoặc bị hủy
    });
}

$(document).ready(function() {
    setDefaultDates(); // Đặt giá trị mặc định cho tu_ngay và den_ngay dựa trên kieu_thong_ke mặc định
    updateDateRange(); // Cập nhật khoảng ngày dựa trên kieu_thong_ke mặc định
    validateAndFetchData(); // Tải dữ liệu dựa trên các giá trị đã cập nhật

    function validateAndFetchData() {
        var tuNgay = $('#tu_ngay').val();
        var denNgay = $('#den_ngay').val();

        // Chuyển đổi chuỗi ngày thành đối tượng Date
        var startDate = new Date(tuNgay);
        var endDate = new Date(denNgay);

        // Kiểm tra nếu 'tu_ngay' lớn hơn 'den_ngay'
        if (startDate > endDate) {
            alert('TỪ NGÀY không được lớn hơn ĐẾN NGÀY.');
            return; // Dừng thực thi hàm nếu điều kiện không hợp lệ
        }

        // Nếu kiểm tra hợp lệ, gọi hàm fetchData
        fetchData();
    }

    $('#increase').on('click', function() {
        adjustDate(1); // Tăng ngày, tuần, tháng, năm
    });
    $('#decrease').on('click', function() {
        adjustDate(-1); // Giảm ngày, tuần, tháng, năm
    });


    function setDefaultDates() {
        var today = new Date();
        var formattedDate = formatDate(today);
        $('#tu_ngay').val(formattedDate);
        $('#den_ngay').val(formattedDate);
    }

    // Gắn sự kiện click với nút load_data_button
    $('#load_data_button').click(function() {
        validateAndFetchData(); // Gọi hàm validateAndFetchData khi nút được nhấp
    })

    // Khi loại thống kê thay đổi, cập nhật khoảng ngày
    $('#kieu_thong_ke').change(function() {
        updateDateRange();
    });

    function updateDateRange() {
        var kieuThongKe = $('#kieu_thong_ke').val();
        var today = new Date();
        var tuNgay, denNgay;

        switch (kieuThongKe) {
            case 'tuan':
                var firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 1)); // Lấy ngày đầu tiên của tuần
                var lastDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + 7)); // Lấy ngày cuối cùng của tuần
                tuNgay = formatDate(firstDayOfWeek);
                denNgay = formatDate(lastDayOfWeek);
                break;
            case 'thang':
                var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1); // Lấy ngày đầu tiên của tháng
                var lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0); // Lấy ngày cuối cùng của tháng
                tuNgay = formatDate(firstDayOfMonth);
                denNgay = formatDate(lastDayOfMonth);
                break;
            case 'nam':
                var firstDayOfYear = new Date(today.getFullYear(), 0, 1); // Lấy ngày đầu tiên của năm
                var lastDayOfYear = new Date(today.getFullYear(), 11, 31); // Lấy ngày cuối cùng của năm
                tuNgay = formatDate(firstDayOfYear);
                denNgay = formatDate(lastDayOfYear);
                break;
            case 'tuy_bien':
                // Đối với trường hợp tùy biến, giữ nguyên giá trị người dùng đã nhập
                tuNgay = $('#tu_ngay').val();
                denNgay = $('#den_ngay').val();
                break;
        }

        // Cập nhật giá trị ngày vào các trường nhập liệu
        $('#tu_ngay').val(tuNgay);
        $('#den_ngay').val(denNgay);
    }

    function adjustDate(direction) {
        var type = $('#kieu_thong_ke').val();
        var tuNgay = new Date($('#tu_ngay').val());
        var denNgay = new Date($('#den_ngay').val());

        switch(type) {
            case 'ngay':
                tuNgay.setDate(tuNgay.getDate() + direction);
                denNgay.setDate(denNgay.getDate() + direction);
                break;
            case 'tuan':
                // Thêm hoặc bớt 7 ngày cho cả tu_ngay và den_ngay
                tuNgay.setDate(tuNgay.getDate() + (7 * direction));
                denNgay.setDate(denNgay.getDate() + (7 * direction));
                break;
            case 'thang':
                tuNgay.setMonth(tuNgay.getMonth() + direction);
                denNgay = new Date(tuNgay.getFullYear(), tuNgay.getMonth() + 1, 0); // Điều chỉnh den_ngay để trùng với ngày cuối cùng của tháng mới
                if (direction > 0) {
                    // Đảm bảo den_ngay là ngày cuối cùng của tháng khi tăng tháng
                    tuNgay.setDate(1); // Đặt tu_ngay là ngày đầu tiên của tháng
                } else {
                    // Khi giảm tháng, giữ ngày của tu_ngay nếu có thể
                    var lastDayOfNewMonth = new Date(tuNgay.getFullYear(), tuNgay.getMonth() + 1, 0).getDate();
                    tuNgay.setDate(Math.min(tuNgay.getDate(), lastDayOfNewMonth));
                }
                break;
            case 'nam':
                tuNgay.setFullYear(tuNgay.getFullYear() + direction);
                denNgay.setFullYear(denNgay.getFullYear() + direction);
                break;
        }

        $('#tu_ngay').val(formatDate(tuNgay));
        $('#den_ngay').val(formatDate(denNgay));
    }

    function validateAndFetchData() {
        var tuNgay = $('#tu_ngay').val();
        var denNgay = $('#den_ngay').val();

        // Chuyển đổi chuỗi ngày thành đối tượng Date
        var startDate = new Date(tuNgay);
        var endDate = new Date(denNgay);

        // Kiểm tra nếu 'tu_ngay' lớn hơn 'den_ngay'
        if (startDate > endDate) {
            alert('TỪ NGÀY không được lớn hơn ĐẾN NGÀY.');
            return; // Dừng thực thi hàm nếu điều kiện không hợp lệ
        }

        // Nếu kiểm tra hợp lệ, gọi hàm fetchData
        fetchData();
    }

    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) 
            month = '0' + month;
        if (day.length < 2) 
            day = '0' + day;

        return [year, month, day].join('-');
    }

})
</script>
@endpush