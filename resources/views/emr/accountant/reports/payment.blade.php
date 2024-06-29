@extends('adminlte::page')

@section('title', 'Báo cáo giao dịch')

@section('content_header')
<h1>
    Báo cáo thống kê
    <small>Giao dịch ngân hàng</small>
</h1>
@stop

@section('content')
@include('emr.partials.search')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="payment-index" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Ngày lập</th>
                    <th>Mã ĐT</th>
                    <th>Tên BN</th>
                    <th>Ngày sinh</th>
                    <th>Địa chỉ</th>
                    <th>Số điện thoại</th>
                    <th>Loại thanh toán</th>
                    <th>Số tiền</th>
                    <th>Mã người lập</th>
                    <th>Tên người lập</th>
                    <th>Khoa điều trị</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    var dataTable;
    $(document).ready(function() {
        function fetchData() {
            if ($.fn.DataTable.isDataTable('#payment-index')) {
                // Nếu đã tồn tại, phá hủy DataTable hiện tại
                dataTable.destroy();
            }
            dataTable = $('#payment-index').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    url: "{{ route('accountant.get-payment') }}",
                    data: {
                        date_from: $('#tu_ngay').val(),
                        date_to: $('#den_ngay').val(),
                        treatment_code: $('#treatment_code').val(),
                    }
                },
                "columns": [
                    { "data": "created_at", "name": "created_at" },
                    { "data": "treatment_code", "name": "treatment_code" },
                    { "data": "patient_name", "name": "patient_name" },
                    { "data": "patient_dob", "name": "patient_dob" },
                    { "data": "patient_address", "name": "patient_address" },
                    { "data": "patient_mobile", "name": "patient_mobile" },
                    { "data": "is_payment", "name": "is_payment" },
                    { "data": "amount", "name": "amount" },
                    { "data": "login_name", "name": "login_name" },
                    { "data": "user_name", "name": "user_name" },
                    { "data": "department_name", "name": "department_name" },
                ],
            });
        }
        fetchData();

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
    });

    $('#export_xlsx').click(function() {
        // Lấy giá trị từ các input
        var treatmentCode = $('#treatment_code').val();
        var fromDate = $('#tu_ngay').val();
        var toDate = $('#den_ngay').val();
        
        // Tạo URL với các tham số query
        var href = '{{ route("accountant.export-payment") }}?' + $.param({
            'treatment_code': treatmentCode,
            'tu_ngay': fromDate,
            'den_ngay': toDate
        });
        
        // Chuyển hướng tới URL với các tham số
        window.location.href = href;
    });
</script>
@endpush