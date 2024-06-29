@extends('adminlte::page')

@section('title', 'Thống kê Ngoại trú')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê Ngoại trú</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Điều kiện</b>
        </div>

        <form method="GET" action="">
            <!-- Chọn khoảng thời gian -->
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-5">
                                    <label for="date_type">Lọc theo</label>
                                </div>
                                <div class="col-sm-7">
                                    <select class="form-control" id="date_type">
                                        <option value="date_in">Ngày vào</option>
                                        <option value="date_out">Ngày ra</option>
                                        <option value="date_fee_lock">Ngày t.toán</option>
                                    </select>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group row">
                            <!-- Dropdown kieu_thong_ke -->
                            <div class="col-sm-12 row">
                                <div class="col-sm-6">
                                    <select class="form-control" id="kieu_thong_ke" name="kieu_thong_ke">
                                        <option value="ngay">Ngày</option>
                                        <option value="tuan">Tuần</option>
                                        <option value="thang">Tháng</option>
                                        <option value="nam">Năm</option>
                                        <option value="tuy_bien">Tùy biến</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <!-- Nút giảm -->
                                    <button type="button" class="btn btn-info" id="decrease">&lt;</button>
                                    <!-- Nút tăng -->
                                    <button type="button" class="btn btn-info" id="increase">&gt;</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <!-- Thêm select box cho thời gian làm mới -->
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="tu_ngay">Refresh</label>
                                </div>
                                <div class="col-sm-9">
                                    <select class="form-control" id="refresh_time">
                                        <option value="0">Don't refresh</option>
                                        <option value="10">10 giây</option>
                                        <option value="30">30 giây</option>
                                        <option value="60">1 phút</option>
                                        <option value="300">5 phút</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="tu_ngay">Từ</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="tu_ngay" name="tu_ngay" value="{{request()->input('tu_ngay', old('tu_ngay'))}}">
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="den_ngay">Đến</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="den_ngay" name="den_ngay" value="{{request()->input('den_ngay', old('den_ngay'))}}">
                                    </div>
                                </div>   
                            </div>
                        </div>
                    </div>
                    <div class="input-group-append">
                        <button type="button" class="btn btn-primary form-control" id="load_data_button" style="visibility: hidden;">Tải dữ liệu...</button>
                    </div>
                </div>
            </div>         
        </form>
    </div>
</div>

<div class="row">
    <!-- Left col -->
    <div class="col-lg-12 connectedSortable">
        <!-- Custom tabs (Charts with tabs)-->
        <div class="nav-tabs-custom">
            <!-- Tabs within a box -->
            <img class="center-block" id="loading-image" src="../images/ajax-loader.gif" style="display: none; padding: 10px;" />
            <div class="tab-content no-padding">
                <!-- Morris chart - Sales -->
                    <img id="patientChart" src="" style="width: 100%;height: auto%;">
            </div>
        </div>
    </div>
</div>

<!-- Phần tử để hiển thị bộ đếm ngược -->
<div id="countdown_timer" style="position: fixed; bottom: 20px; right: 20px; display: none; color: red;">
    Refresh: <span id="timer"></span>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">

let refreshIntervalId;
let countdownIntervalId;

function fetchData(){
    $('#loading-image').show();
    // Lấy giá trị của tu_ngay và den_ngay
    var tuNgay = document.getElementById('tu_ngay').value;
    var denNgay = document.getElementById('den_ngay').value;
    var dateType = document.getElementById('date_type').value;

    // Tạo URL với các tham số
     var url = "{{route('khth.patient-chart')}}" + "?tu_ngay=" + encodeURIComponent(tuNgay) + "&den_ngay=" + encodeURIComponent(denNgay) + "&date_type=" + encodeURIComponent(dateType);

    $.ajax({
      url: url,
      type: "GET",
      dataType: 'json',
    })
    .done(function(data) {
        // Cập nhật src của ảnh với dữ liệu nhận được
        document.getElementById('patientChart').src = 'data:image/png;base64,' + data.image;
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      // Nếu yêu cầu thất bại, hiển thị lỗi trong console
      console.log(textStatus + ': ' + errorThrown);
    })
    .always(function() {
        $('#loading-image').hide();
        // Kiểm tra xem liệu có cần làm mới tự động không
        const refreshTime = parseInt(document.getElementById('refresh_time').value);
        if (refreshTime !== 0) {
            startCountdown(refreshTime);
        }
    });
}


document.getElementById('refresh_time').addEventListener('change', function() {
    const refreshTime = parseInt(this.value);
    
    // Hủy bỏ hẹn giờ tự động làm mới và đếm ngược trước đó (nếu có)
    clearInterval(refreshIntervalId);
    clearInterval(countdownIntervalId);
    document.getElementById('countdown_timer').style.display = 'none'; // Ẩn bộ đếm ngược
    
    if (refreshTime !== 0) {
        // Thiết lập hẹn giờ tự động làm mới dựa trên lựa chọn
        refreshIntervalId = setInterval(fetchData, refreshTime * 1000);

        // Hiển thị và bắt đầu đếm ngược
        document.getElementById('countdown_timer').style.display = 'block';
        startCountdown(refreshTime);
    }

    // Lưu trạng thái lựa chọn vào localStorage
    localStorage.setItem('selectedRefreshTime', refreshTime);
});

function startCountdown(seconds) {
    clearInterval(countdownIntervalId); // Đặt lại bộ đếm ngược nếu nó đang chạy
    let remainingTime = seconds;
    document.getElementById('timer').textContent = remainingTime;
    document.getElementById('countdown_timer').style.display = 'block'; // Hiển thị bộ đếm ngược

    countdownIntervalId = setInterval(() => {
        remainingTime -= 1;
        document.getElementById('timer').textContent = remainingTime;
        if (remainingTime <= 0) {
            clearInterval(countdownIntervalId);
            document.getElementById('countdown_timer').style.display = 'none'; // Ẩn bộ đếm ngược khi hoàn thành
        }
    }, 1000);
}

document.addEventListener("DOMContentLoaded", function() {
    // Đặt giá trị mặc định cho tu_ngay và den_ngay là ngày hiện tại
    setDefaultDates();

    document.getElementById('increase').addEventListener('click', function() {
        adjustDate(1); // Tăng ngày, tuần, tháng, năm
    });
    document.getElementById('decrease').addEventListener('click', function() {
        adjustDate(-1); // Giảm ngày, tuần, tháng, năm
    });

    function setDefaultDates() {
        var today = new Date();
        var formattedDate = formatDate(today);
        document.getElementById('tu_ngay').value = formattedDate;
        document.getElementById('den_ngay').value = formattedDate;
    }

    // Lắng nghe sự kiện click của nút 'Tải dữ liệu'
    $('#load_data_button').click(function() {
        // Gọi hàm fetchData khi người dùng click vào nút
        validateAndFetchData();
    });

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

    function adjustDate(direction) {
        var type = document.getElementById('kieu_thong_ke').value;
        var tuNgayElement = document.getElementById('tu_ngay');
        var denNgayElement = document.getElementById('den_ngay');
        var tuNgay = new Date(tuNgayElement.value);
        var denNgay = new Date(denNgayElement.value);

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

        tuNgayElement.value = formatDate(tuNgay);
        denNgayElement.value = formatDate(denNgay);

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

    fetchData(); // Gọi hàm lần đầu tiên khi trang được tải
    
    //Gọi hàm lần đầu tiên khi trang được tải thì disable tu_ngay, den ngay
    $('#tu_ngay').prop('disabled', true);
    $('#den_ngay').prop('disabled', true);

    // Thiết lập setInterval để gọi hàm fetchData mỗi 300000 milliseconds (5 phút)
    //setInterval(fetchData, 300000);

    // Add a listener to the date_type dropdown to call fetchData when it changes
    document.getElementById('date_type').addEventListener('change', function() {
        fetchData(); // Call fetchData with the updated dateType
    });

    // Listener cho dropdown thay đổi kiểu thống kê
    document.getElementById('kieu_thong_ke').addEventListener('change', function() {
        // Cập nhật giá trị của tu_ngay và den_ngay dựa vào lựa chọn
        // và gọi lại hàm fetchData để cập nhật dữ liệu theo lựa chọn mới
        var today = new Date();
        var tuNgay = document.getElementById('tu_ngay');
        var denNgay = document.getElementById('den_ngay');

        switch (this.value) {
            case 'ngay':
                tuNgay.value = denNgay.value = formatDate(today);
                break;
            case 'tuan':
                var firstDayOfWeek = new Date(today.setDate(today.getDate() - today.getDay() + (today.getDay() === 0 ? -6 : 1))); // Điều chỉnh cho đầu tuần là Thứ Hai
                tuNgay.value = formatDate(firstDayOfWeek);
                denNgay.value = formatDate(new Date(firstDayOfWeek.setDate(firstDayOfWeek.getDate() + 6)));
                break;
            case 'thang':
                var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                var lastDayOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                tuNgay.value = formatDate(firstDayOfMonth);
                denNgay.value = formatDate(lastDayOfMonth);
                break;
            case 'nam':
                var firstDayOfYear = new Date(today.getFullYear(), 0, 1);
                var lastDayOfYear = new Date(today.getFullYear(), 12, 0);
                tuNgay.value = formatDate(firstDayOfYear);
                denNgay.value = formatDate(lastDayOfYear);
                break;
        }
        
        // Kiểm tra giá trị của dropdown
        var kieuThongKe = $(this).val();
        if (kieuThongKe == 'tuy_bien') {
            // Nếu là 'tuy_bien', cho phép nhập 'tu_ngay' và 'den_ngay'
            $('#tu_ngay').prop('disabled', false);
            $('#den_ngay').prop('disabled', false);
            $('#decrease').hide();
            $('#increase').hide();
            // Hiện nút 'Tải dữ liệu'
            $('#load_data_button').css('visibility', 'visible');
        } else {
            // Nếu không phải 'tuy_bien', không cho phép nhập và làm mờ 'tu_ngay' và 'den_ngay'
            $('#tu_ngay').prop('disabled', true);
            $('#den_ngay').prop('disabled', true);
            $('#decrease').show();
            $('#increase').show();
            // Ẩn nút 'Tải dữ liệu'
            $('#load_data_button').css('visibility', 'hidden');

            validateAndFetchData(); // Gọi hàm kiểm tra và tải dữ liệu
        }
    });

});

</script>
@endpush