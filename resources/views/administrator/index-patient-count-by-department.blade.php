@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Report
    <small><strong>SỐ LƯỢNG BỆNH NHÂN HIỆN DIỆN TẠI CÁC KHOA NGÀY: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</strong></small>
</h1>
@stop

@push('after-styles')
<style>
    .refresh-after {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 5px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .refresh-after select {
        height: 28px;
        font-size: 12px;
        padding: 2px 5px;
    }

    .refresh-icon {
        font-size: 14px;
        color: #007bff;
    }

    #index {
    font-size: 120%;
}
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('css/daterangepicker.css') }}" />
@endpush

@section('content')
<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="index" class="table table-bordered display table-hover responsive nowrap datatable dtr-inline table-condensed" width="100%">
            <thead>
                <tr>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">STT</th>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">TÊN KHOA</th>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">GIƯỜNG KH</th>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">GIƯỜNG TK</th>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">TỔNG SỐ BN</th>
                    <th colspan="2" class="text-center align-middle p-1" style="vertical-align: middle">TRONG ĐÓ</th>
                    <th rowspan="2" class="text-center align-middle p-1" style="vertical-align: middle">TỶ LỆ</th>
                </tr>
                <tr>
                    <th class="text-center align-middle p-1" style="vertical-align: middle">BHYT</th>
                    <th class="text-center align-middle p-1" style="vertical-align: middle">THU PHÍ</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<div id="refresh-after" class="refresh-after">
    <i id="ajax-spinner" class="fa fa-spinner fa-spin" style="display:none; font-size:16px; color:#007bff;"></i>
    <span id="countdown-timer">00:00</span>
    <select id="refreshInterval" class="form-control">
        <option value="300000">5 min</option>
        <option value="600000">10 min</option>
        <option value="900000">15 min</option>
        <option value="1800000">30 min</option>
    </select>
</div>

@stop

@push('after-scripts')
<script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
<script src="{{ asset('vendor/numeral/locales.js') }}"></script>

<script type="text/javascript">
    function loadData() {
        $.ajax({
            url: "{{ route('reports-administrator.fetch-patient-count-by-department') }}",
            method: 'GET',
            success: function(response) {
                var tbody = '';
                var totalTheory = 0;
                var totalReality = 0;
                var totalBHYT = 0;
                var totalVienPhi = 0;

                response.forEach(function(row, index) {
                    tbody += '<tr>';
                    tbody += '<td class="text-center align-middle">' + (index + 1) + '</td>';
                    tbody += '<td>' + row.department_name + '</td>';
                    tbody += '<td class="text-center align-middle">' + (row.theory_patient_count ? numeral(row.theory_patient_count).format('0,0') : '') + '</td>';
                    tbody += '<td class="text-center align-middle">' + (row.reality_patient_count ? numeral(row.reality_patient_count).format('0,0') : '') + '</td>';
                    tbody += '<td class="text-center align-middle">' + numeral(row.total).format('0,0') + '</td>';
                    tbody += '<td class="text-center align-middle">' + numeral(row.bhyt_count).format('0,0') + '</td>';
                    tbody += '<td class="text-center align-middle">' + numeral(row.vien_phi_count).format('0,0') + '</td>';
                    tbody += '<td class="text-center align-middle">' + (row.rate ? numeral(row.rate).format('0.00') + '%' : '') + '</td>';
                    tbody += '</tr>';

                    // Calculate totals
                    totalTheory += parseInt(row.theory_patient_count || 0);
                    totalReality += parseInt(row.reality_patient_count || 0); 
                    totalBHYT += parseInt(row.bhyt_count || 0);
                    totalVienPhi += parseInt(row.vien_phi_count || 0);
                });
                // Add total row
                tbody += '<tr style="font-weight: bold">';
                tbody += '<td class="text-center align-middle" colspan="2">TỔNG CỘNG</td>';
                tbody += '<td class="text-center align-middle">' + numeral(totalTheory).format('0,0') + '</td>';
                tbody += '<td class="text-center align-middle">' + numeral(totalReality).format('0,0') + '</td>';
                tbody += '<td class="text-center align-middle">' + numeral(totalBHYT + totalVienPhi).format('0,0') + '</td>';
                tbody += '<td class="text-center align-middle">' + numeral(totalBHYT).format('0,0') + '</td>';
                tbody += '<td class="text-center align-middle">' + numeral(totalVienPhi).format('0,0') + '</td>';
                tbody += '<td class="text-center align-middle"></td>';
                tbody += '</tr>';

                $('#index tbody').html(tbody);
            },
            error: function(xhr, error, code) {
                console.log('Error:', error);
                console.log('Code:', code);
                console.log('XHR:', xhr);
            }
        });
    }

    let refreshInterval = parseInt($("#refreshInterval").val()); // Lấy giá trị mặc định
    let countdown = refreshInterval / 1000; // Chuyển đổi sang giây
    let refreshTimer;

    // Hàm cập nhật bộ đếm ngược
    function updateCountdown() {
        let minutes = Math.floor(countdown / 60);
        let seconds = countdown % 60;
        $("#countdown-timer").text(
            (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds
        );
    }

    // Hàm bắt đầu countdown và refresh dữ liệu
    function startAutoRefresh() {
        clearInterval(refreshTimer); // Xóa bộ đếm cũ nếu có
        countdown = refreshInterval / 1000; // Reset lại thời gian

        loadData(); // Chạy ngay lần đầu tiên
        updateCountdown(); // Hiển thị giá trị ban đầu

        refreshTimer = setInterval(function () {
            countdown--;
            updateCountdown();

            if (countdown <= 0) {
                loadData(); // Gọi lại hàm load dữ liệu
                countdown = refreshInterval / 1000; // Reset lại bộ đếm
            }
        }, 1000);
    }

    // Sự kiện thay đổi giá trị trong select box
    $("#refreshInterval").change(function () {
        refreshInterval = parseInt($(this).val()); // Cập nhật khoảng thời gian mới
        startAutoRefresh(); // Restart countdown
    });

    $(document).ready(function () {
        $(document).ajaxStart(function () {
            $('#ajax-spinner').show();
        });

        $(document).ajaxStop(function () {
            $('#ajax-spinner').hide();
        });
    });

    $(document).ready(function() {
        startAutoRefresh();
    });
</script>

@endpush
