@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@push('after-styles')
<style>
    #qrCodeContainer {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100%;
    }
    #qrCodeContainer img {
        max-width: 100%; /* Điều chỉnh kích thước mã QR không vượt quá kích thước của container */
        max-height: 80vh; /* Giới hạn chiều cao mã QR để không vượt quá chiều cao của màn hình */
    }
</style>
@endpush

@section('content_header')

<h1>
    Bệnh án điện tử
    <small>Tra soát hồ sơ bệnh án</small>
</h1>
{{ Breadcrumbs::render('emr.index') }}
@stop

@section('content')
@include('includes.message')
@include('emr.search')
<div class="panel panel-default">
    <div class="panel-heading">
        Danh sách hồ sơ
    </div>
    <div class="panel-body table-responsive">
        <table id="emr-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã ĐT</th>
                    <th>Mã BN</th>
                    <th>Tên BN</th>
                    <th>Năm sinh</th>
                    <th>Mã thẻ</th>
                    <th>Diện điều trị</th>
                    <th>Đối tượng</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Mã QR</th>
                    <th>Số ra viện</th>
                    <th>Kết quả</th>
                    <th>Loại ra viện</th>
                    <th>Lưu tạm</th>
                    <th>Khoa kết thúc</th>
                    <th>Hành động</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal QR Code -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label>QR code Tra cứu kết quả</label>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="qrCodeContainer"></div> <!-- Nơi mã QR sẽ được tạo -->
            </div>
        </div>
    </div>
</div>

<!-- Modal thông báo kết quả -->
<div class="modal fade" id="resultModal" tabindex="-1" role="dialog" aria-labelledby="resultModalLabel" aria-hidden="true"
data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modal-title" id="resultModalLabel">Chọn kết quả xử lý</label>
                <button type="button" class="btn btn-primary" id="saveBtn">Lưu...</button>
                <button type="button" class="btn btn-warning" id="cancelBtn">Hủy...</button>
            </div>
            <div class="modal-body" id="resultModalContent">
                <!-- Nơi hiển thị thông báo kết quả -->
            </div>
        </div>
    </div>
</div>

@stop
@push('after-scripts')
<!-- Đoạn này đặt trong phần <head> hoặc cuối <body> của tệp HTML -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
<script>
var checkoutUrlPattern = "{{ route('accountant.checkout', ['id' => '__id__']) }}";
var depositUrlPattern = "{{ route('accountant.deposit', ['id' => '__id__']) }}";
var canPayment = @json(auth()->user()->hasRole('thungan') || auth()->user()->hasRole('superadministrator'));

$(document).ready( function () {
    // Khi modal 'resultModal' được đóng
    $('#resultModal').on('hidden.bs.modal', function() {
        $.ajax({
            url: '{{ route("accountant.clearBroadcast") }}', // Thay thế bằng URL backend của bạn
            type: 'GET',
            success: function(response) {
                console.log(response);
            },
            error: function(xhr, status, error) {
            }
        });
    });

    // Định nghĩa sự kiện khi form được submit
    $("form").submit(function(e) {
        // Lấy giá trị của date_type
        var dateType = $('#date_type').val();

        // Lưu giá trị vào local storage để sử dụng sau này
        localStorage.setItem('selectedDateType', dateType);

        // Tiếp tục gửi form
        // Không cần phải ngăn chặn sự kiện mặc định submit của form
    });

    // Khi form được tải, kiểm tra local storage và set giá trị đã lưu cho date_type
    var savedDateType = localStorage.getItem('selectedDateType');
    if (savedDateType) {
        $('#date_type').val(savedDateType);
    }

    $('#emr-index').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: "{{ route('emr.get-list-emr-treatment') }}",
            data: {
                dateType: $('#date_type').val(),
                date_from: $('#date_from').val(),
                date_to: $('#date_to').val(),
                treatment_code: $('#treatment_code').val(),
                department: $('#department').val(),
                treatment_type: $('#treatment_type').val(),
                patient_type: $('#patient_type').val(),
            }
        },
        "columns": [
            { "data": "treatment_code", "name": "treatment_code" },
            { "data": "tdl_patient_code", "name": "tdl_patient_code" },
            { "data": "tdl_patient_name", "name": "tdl_patient_name" },
            { "data": "tdl_patient_dob", "name": "tdl_patient_dob" },
            { "data": "tdl_hein_card_number", "name": "tdl_hein_card_number" },
            { "data": "treatment_type_name", "name": "his_treatment_type.treatment_type_name" },
            { "data": "patient_type_name", "name": "his_patient_type.patient_type_name" },
            { "data": "in_time", "name": "in_time" },
            { "data": "out_time", "name": "out_time" },
            {
                "data": "qr_code",
                "name": "qr_code",
                "searchable": false, // Vô hiệu hóa tìm kiếm cho cột này
                "orderable": false, // Đặt là false nếu bạn cũng muốn vô hiệu hóa chức năng sắp xếp cho cột này
                "render": function(data, type, row) {
                    // Xác định số điện thoại dựa trên ưu tiên
                    var phoneNumber = row.tdl_patient_mobile || row.tdl_patient_phone || row.tdl_patient_relative_mobile || row.tdl_patient_relative_phone || '';
                    // Tạo URL cho mã QR, thêm số điện thoại (nếu có)
                    var qrCodeUrl = `http://benhviendakhoanongnghiep.vn:6868/view-guide-content?treatment_code=${row.treatment_code}&phone=${encodeURIComponent(phoneNumber)}`;
                    var id = row.treatment_code;

                    // Trả về HTML cho nút hiển thị mã QR với URL đã được tạo
                    var actionButtons = `<button class="btn btn-sm btn-info show-qr" data-qr-url="${qrCodeUrl}"><span class="glyphicon glyphicon-qrcode"></span> Mã QR</button>`;

                    if (canPayment) {
                        actionButtons += `<button id="payment" class="btn btn-sm btn-warning" data-id="${id}"><span class="glyphicon glyphicon-qrcode"></span> T.toán</button>
                                          <button id="deposit" class="btn btn-sm btn-success" data-id="${id}"><span class="glyphicon glyphicon-qrcode"></span> T.ứng</button>`;
                    }
                    return actionButtons;
                }
            },
            { "data": "end_code", "name": "end_code" },
            { "data": "treatment_result_name", "name": "his_treatment_result.treatment_result_name" },
            { "data": "treatment_end_type_name", "name": "his_treatment_end_type.treatment_end_type_name" },
            { "data": "temp_save", "name": "temp_save" },
            { "data": "department_name", "name": "his_department.department_name" },
            { "data": "action", "name": "action" },
        ],
    });

    // Khi nhấn nút Lưu
    $('#saveBtn').click(function() {
        // Logic lưu dữ liệu ở đây
        // Hiển thị hộp thoại xác nhận
        var isConfirmed = confirm("Bạn có chắc chắn không ?");
        if (isConfirmed) {
            var paymentData = $('#paymentData');

            var canThanhToan = paymentData.data('can-thanh-toan');
            if (canThanhToan <= 0) {
                // Nếu can_thanh_toan <= 0, hiển thị thông báo và không thực hiện AJAX call
                console.log("Không cần thực hiện thanh toán.");
                $('#resultModal').modal('hide');
                return; // Dừng xử lý sự kiện này
            }

            var dataToSend = {
                _token: "{{ csrf_token() }}", // Token CSRF cho Laravel
                treatment_code: paymentData.data('treatment-code'),
                tdl_patient_name: paymentData.data('tdl-patient-name'),
                tdl_patient_dob: paymentData.data('tdl-patient-dob'),
                tdl_patient_address: paymentData.data('tdl-patient-address'),
                tdl_patient_mobile: paymentData.data('tdl-patient-mobile'),
                tdl_patient_relative_mobile: paymentData.data('tdl-patient-relative-mobile'),
                is_payment: paymentData.data('is-payment'),
                can_thanh_toan: paymentData.data('can-thanh-toan'),
                department_name: paymentData.data('department-name'),
                // Lấy thêm dữ liệu khác tương tự
            };
            $.ajax({
                url: '{{ route("accountant.save-payment") }}', // Thay đổi '/your-backend-url' thành URL thực của bạn
                type: 'POST',
                data: dataToSend,
                success: function(response) {
                    // Xử lý phản hồi ở đây
                    console.log(response);
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    // Xử lý lỗi ở đây
                    console.error(error);
                }
            });
            $('#resultModal').modal('hide');
        } else {
            // Người dùng chọn "Cancel", có thể hiển thị thông báo hoặc không làm gì
            console.log("Hành động đã bị hủy bỏ."); // Hoặc xử lý khác tuỳ ý
        }
        
    });

    // Khi nhấn nút Hủy
    $('#cancelBtn').click(function() {
        // Logic lưu dữ liệu ở đây
        // Hiển thị hộp thoại xác nhận
        var isConfirmed = confirm("Bạn có chắc chắn không ?");
        if (isConfirmed) {
            // Người dùng chọn "OK", thực hiện các hành động tiếp theo ở đây
            console.log("Hành động được thực hiện."); // Hoặc thực hiện một hành động cụ thể
            // Ví dụ: Đóng modal, gửi dữ liệu form,...
            $('#resultModal').modal('hide');
        } else {
            // Người dùng chọn "Cancel", có thể hiển thị thông báo hoặc không làm gì
            console.log("Hành động đã bị hủy bỏ."); // Hoặc xử lý khác tuỳ ý
        }
    });

});

$(document).on('click', '.show-qr', function() {
    var qrUrl = $(this).data('qr-url');
    var logoURI = '/images/logo.png'; // Đường dẫn logo mặc định

    // Xóa mã QR cũ nếu có
    $('#qrCodeContainer').empty();

    // Tạo mã QR mới với logo
    var qrCodeContainer = document.getElementById("qrCodeContainer");
    var qr = new QRCode(qrCodeContainer, {
        text: qrUrl,
        width: 300,
        height: 300,
        correctLevel: QRCode.CorrectLevel.H
    });

    // Thêm logo vào mã QR
    var img = new Image();
    img.src = logoURI;
    img.onload = function() {
        var canvas = qrCodeContainer.querySelector('canvas');
        var ctx = canvas.getContext('2d');
        var logoSize = canvas.width / 5;
        var logoX = (canvas.width - logoSize) / 2;
        var logoY = (canvas.height - logoSize) / 2;
        ctx.drawImage(img, logoX, logoY, logoSize, logoSize);
    };

    // Hiển thị modal
    $('#qrModal').modal('show');
});

$(document).on('click', '#payment', function() {
    var id = $(this).data('id'); // Lấy dữ liệu bạn cần từ attribute data-id
    // Thay thế placeholder __id__ với giá trị thực của id
    var checkoutUrl = checkoutUrlPattern.replace('__id__', id);

    $.ajax({
        url: checkoutUrl, // Thay thế bằng URL backend của bạn
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}' // CSRF token cho Laravel
        },
        success: function(response) {
            // Cập nhật nội dung và hiển thị modal kết quả
            $('#resultModalContent').html(response.html); // Giả sử 'response.message' là thông điệp bạn muốn hiển thị
            $('#resultModal').modal('show');
        },
        error: function(xhr, status, error) {
            // Xử lý lỗi
            $('#resultModalContent').html('Có lỗi xảy ra: ' + error);
            $('#resultModal').modal('show');
        }
    });
});

$(document).on('click', '#deposit', function() {
    var id = $(this).data('id'); // Lấy dữ liệu bạn cần từ attribute data-id
    // Thay thế placeholder __id__ với giá trị thực của id
    var depositUrl = depositUrlPattern.replace('__id__', id);

    $.ajax({
        url: depositUrl, // Thay thế bằng URL backend của bạn
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}' // CSRF token cho Laravel
        },
        success: function(response) {
            // Cập nhật nội dung và hiển thị modal kết quả
            $('#resultModalContent').html(response.html); // Giả sử 'response.message' là thông điệp bạn muốn hiển thị
            $('#resultModal').modal('show');
        },
        error: function(xhr, status, error) {
            // Xử lý lỗi
            $('#resultModalContent').html('Có lỗi xảy ra: ' + error);
            $('#resultModal').modal('show');
        }
    });
});

</script>
@endpush