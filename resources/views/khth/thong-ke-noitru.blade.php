@extends('adminlte::page')

@section('title', 'Nội trú')

@section('content_header')
  <h1>
    KHTH
    <small>Thống kê Nội trú</small>
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
                                <option value="date_processing">Đang điều trị</option>
                                <option value="date_in">Ngày vào</option>
                                <option value="date_out">Ngày ra</option>
                            </select>
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

function fetchData(){
    $('#loading-image').show();
    var date_type = $('#date_type').val();
    var url = ''; // Khởi tạo URL rỗng
    var data = {}; // Khởi tạo dữ liệu gửi đi rỗng

    // Xác định URL và dữ liệu gửi đi dựa trên giá trị của date_type
    if (date_type === 'date_processing') {
        url = '{{ route("khth.inpatient-processing") }}'; // Giả sử đây là endpoint cho Đang điều trị
        // Không cần dữ liệu gửi đi trong trường hợp này
        data = {
            'tu_ngay': $('#tu_ngay').val(),
            'den_ngay': $('#den_ngay').val(),
            'date_type': $('#date_type').val()
        };
    } else {
        url = '{{ route("khth.inpatient-processing") }}'; // Giả sử đây là endpoint cho Ngày vào/Ngày ra
        data = {
            'tu_ngay': $('#tu_ngay').val(),
            'den_ngay': $('#den_ngay').val(),
            'date_type': $('#date_type').val()
        };
    }

    // Thực hiện gọi AJAX
    $.ajax({
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
    });
}

$(document).ready(function() {
    // Đặt giá trị mặc định cho tu_ngay và den_ngay là ngày hiện tại
    setDefaultDates();

    // Ẩn hoặc hiện các trường ngày và nút load dữ liệu dựa trên lựa chọn của người dùng
    $('#date_type').change(function() {
        toggleDateInputsAndButton($(this).val());
    });

    // Gắn sự kiện click với nút load_data_button
    $('#load_data_button').click(function() {
        validateAndFetchData(); // Gọi hàm validateAndFetchData khi nút được nhấp
    })

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

    function setDefaultDates() {
        var today = new Date();
        var formattedDate = formatDate(today);
        $('#tu_ngay').val(formattedDate);
        $('#den_ngay').val(formattedDate);
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

    function toggleDateInputsAndButton(value) {
        if (value === 'date_processing') {
            // Ẩn các trường tu_ngay và den_ngay và nút load_data_button
            $('#tu_ngay').closest('.form-group').hide();
            $('#den_ngay').closest('.form-group').hide();
            $('#load_data_button').hide(); // Ẩn nút

            //Gọi hàm fetchData để tải lại dữ liệu trong trường hợp này
            fetchData();
        } else {
            // Hiện các trường tu_ngay và den_ngay và nút load_data_button
            $('#tu_ngay').closest('.form-group').show();
            $('#den_ngay').closest('.form-group').show();
            $('#load_data_button').show(); // Hiện nút
        }
    }

    // Khởi tạo lần đầu để xử lý giá trị mặc định của date_type
    toggleDateInputsAndButton($('#date_type').val());

    //fetchData(); // Gọi hàm lần đầu tiên khi trang được tải xong

})
</script>
@endpush