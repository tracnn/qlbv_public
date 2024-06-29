@extends('adminlte::page')

@section('title', 'Gia tăng chi phí')

@section('content_header')
  <h1>
    KHTH
    <small>Gia Tăng Chi Phí NĐ75</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="kieu_thong_ke">Dữ liệu lấy theo</label>
                        <select class="form-control" id="kieu_thong_ke">
                            <option value="tuan">Tuần</option>
                            <option value="thang">Tháng</option>
                            <option value="nam">Năm</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-1 row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="kieu_thong_ke">Giảm</label>
                            <button type="button" class="btn btn-info form-control" id="decrease">&lt;</button>
                        </div>    
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <label for="kieu_thong_ke">Tăng</label>
                            <button type="button" class="btn btn-info form-control" id="increase">&gt;</button>
                        </div>    
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
                <div class="col-sm-1">
                    <div class="form-group row">
                        <label for="export_xlsx">XLS</label>
                        <button type="button" class="btn btn-success form-control" id="export_xlsx">Export...</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="treatment_type">Diện điều trị</label>
                        <select class="form-control" id="treatment_type">
                            <option value="all">Tất cả</option>
                            <option value="kham">Khám</option>
                            <option value="noitru">Nội trú</option>
                            <option value="ngoaitru">Ngoại trú</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-1">
                    <div class="form-group row">
                        <label for="compare_type">Kiểu so sánh</label>
                        <select class="form-control" id="compare_type">
                            <option value="kytruoc">Kỳ trước</option>
                            <option value="cungky">Cùng kỳ</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary form-control" id="load_data_button">Tải dữ liệu...</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="panel panel-default">
    <div class="panel-body">
        <div id="chiphi_table_container" class="table-responsive">
            <!-- Partial view sẽ được load ở đây -->
        </div>
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

        var url = ''; // Khởi tạo URL rỗng
        var data = {}; // Khởi tạo dữ liệu gửi đi rỗng

        // Xác định URL và dữ liệu gửi đi dựa trên giá trị của date_type

        url = '{{ route("khth.chi-phi-nd75-processing") }}'; // Giả sử đây là endpoint cho Đang điều trị
        // Không cần dữ liệu gửi đi trong trường hợp này
        data = {
            'tu_ngay': $('#tu_ngay').val(),
            'den_ngay': $('#den_ngay').val(),
            'kieu_thong_ke': $('#kieu_thong_ke').val()
        };

        // Thực hiện gọi AJAX
        currentAjaxRequest = $.ajax({
          url: url,
          type: "GET",
          dataType: 'html',
          data: data,
        })
        .done(function(data) {
            // Cập nhật src của ảnh với dữ liệu nhận được
            //console.log(data);
            $('#chiphi_table_container').html(data);
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
          // Nếu yêu cầu thất bại, hiển thị lỗi trong console
          console.log(textStatus + ': ' + errorThrown);
        })
        .always(function() {
            currentAjaxRequest = null; // Đặt lại biến khi yêu cầu hoàn thành hoặc bị hủy
        });
    }

    $(document).ready(function() {
        setDefaultDates(); // Đặt giá trị mặc định cho tu_ngay và den_ngay dựa trên kieu_thong_ke mặc định
        updateDateRange(); // Cập nhật khoảng ngày dựa trên kieu_thong_ke mặc định

        function setDefaultDates() {
            var today = new Date();
            var formattedDate = formatDate(today);
            $('#tu_ngay').val(formattedDate);
            $('#den_ngay').val(formattedDate);
        }

        // Khi loại thống kê thay đổi, cập nhật khoảng ngày
        $('#kieu_thong_ke').change(function() {
            updateDateRange();
        });

        $('#increase').on('click', function() {
            adjustDate(1); // Tăng ngày, tuần, tháng, năm
        });
        $('#decrease').on('click', function() {
            adjustDate(-1); // Giảm ngày, tuần, tháng, năm
        });

        // Gắn sự kiện click với nút load_data_button
        $('#load_data_button').click(function() {
            validateAndFetchData(); // Gọi hàm validateAndFetchData khi nút được nhấp
        })


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


    });
</script>

@endpush