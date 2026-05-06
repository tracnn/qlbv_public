<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $organizationName }} — Dashboard Phòng Khám</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Header ── */
        .header {
            background: #1a3c5e;
            color: #fff;
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }
        .header .hospital-name {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header .clock {
            font-size: 1.2rem;
            font-weight: 500;
            text-align: right;
        }

        /* ── Stats bar ── */
        .stats-bar {
            background: #fff;
            border-bottom: 2px solid #e0e6ed;
            padding: 8px 24px;
            display: flex;
            align-items: center;
            gap: 32px;
            flex-shrink: 0;
        }
        .stats-bar .stat-item {
            font-size: 1.05rem;
            color: #333;
        }
        .stats-bar .stat-value {
            font-weight: 700;
            color: #1a3c5e;
            font-size: 1.2rem;
        }
        .stat-chua .stat-value { color: rgb(255, 99, 132); }
        .stat-dang .stat-value { color: rgb(255, 159, 64); }
        .stat-da   .stat-value { color: rgb(75, 192, 100); }
        .stats-bar .separator {
            color: #ccc;
            font-size: 1.4rem;
        }

        /* ── Chart container ── */
        .chart-wrapper {
            flex: 1;
            padding: 12px 20px 8px;
            position: relative;
            min-height: 0;
        }
        #chart-phong-kham {
            width: 100% !important;
            height: 100% !important;
        }

        /* ── Loading overlay ── */
        .loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(244,246,249,0.85);
            font-size: 1.2rem;
            color: #666;
        }
        .loading-overlay.hidden { display: none; }
    </style>
</head>
<body>

    <!-- Header: tên bệnh viện + đồng hồ -->
    <div class="header">
        <div class="hospital-name">&#x1F3E5; {{ $organizationName }}</div>
        <div class="clock" id="clock"></div>
    </div>

    <!-- Stats bar -->
    <div class="stats-bar">
        <div class="stat-item">
            Tổng lượt khám:
            <span class="stat-value" id="tong-luot-kham">—</span>
        </div>
        <div class="separator">•</div>
        <div class="stat-item stat-chua">
            Chưa khám:
            <span class="stat-value" id="tong-chua-thuc-hien">—</span>
        </div>
        <div class="separator">•</div>
        <div class="stat-item stat-dang">
            Đang khám:
            <span class="stat-value" id="tong-dang-thuc-hien">—</span>
        </div>
        <div class="separator">•</div>
        <div class="stat-item stat-da">
            Đã khám:
            <span class="stat-value" id="tong-da-thuc-hien">—</span>
        </div>
        <div class="separator">•</div>
        <div class="stat-item">
            Số phòng:
            <span class="stat-value" id="tong-so-phong">—</span>
        </div>
    </div>

    <!-- Biểu đồ -->
    <div class="chart-wrapper">
        <div class="loading-overlay" id="loading">Đang tải dữ liệu...</div>
        <canvas id="chart-phong-kham"></canvas>
    </div>

    <!-- JS: jQuery + Chart.js v2 (local) -->
    <script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
    <!-- chartjs-plugin-datalabels v0.7.0 — tương thích Chart.js v2 -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@0.7.0/dist/chartjs-plugin-datalabels.min.js"></script>
    <script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
    <script src="{{ asset('vendor/numeral/locales.js') }}"></script>

    <script>
    /* ── Đồng hồ thực: cập nhật mỗi giây ── */
    function updateClock() {
        var now = new Date();
        var options = {
            weekday: 'long',
            year:    'numeric',
            month:   '2-digit',
            day:     '2-digit',
            hour:    '2-digit',
            minute:  '2-digit',
            second:  '2-digit',
            hour12:  false,
            timeZone: 'Asia/Ho_Chi_Minh'
        };
        document.getElementById('clock').textContent = now.toLocaleString('vi-VN', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    /* ── Biểu đồ phòng khám ── */
    numeral.locale('vi');
    var chartInstance = null;

    function loadChart() {
        $.ajax({
            url: '{{ route("khth.chart-phong-kham") }}',
            type: 'GET',
            dataType: 'json',
            timeout: 15000
        })
        .done(function(data) {
            $('#loading').addClass('hidden');

            // Cập nhật stats header
            $('#tong-luot-kham').text(numeral(data.tong_luot_kham).format('0,0'));
            $('#tong-chua-thuc-hien').text(numeral(data.tong_chua_thuc_hien).format('0,0'));
            $('#tong-dang-thuc-hien').text(numeral(data.tong_dang_thuc_hien).format('0,0'));
            $('#tong-da-thuc-hien').text(numeral(data.tong_da_thuc_hien).format('0,0'));
            $('#tong-so-phong').text(data.tong_so_phong);

            // Xóa chart cũ trước khi tạo mới
            if (chartInstance) {
                chartInstance.destroy();
                chartInstance = null;
            }

            var ctx = document.getElementById('chart-phong-kham').getContext('2d');

            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Chưa thực hiện',
                            data: data.chua_thuc_hien,
                            backgroundColor: 'rgba(255, 99, 132, 0.85)',
                            stack: 'stack'
                        },
                        {
                            label: 'Đang thực hiện',
                            data: data.dang_thuc_hien,
                            backgroundColor: 'rgba(255, 159, 64, 0.85)',
                            stack: 'stack'
                        },
                        {
                            label: 'Đã thực hiện',
                            data: data.da_thuc_hien,
                            backgroundColor: 'rgba(75, 192, 100, 0.85)',
                            stack: 'stack'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom',
                        labels: {
                            fontSize: 14,
                            padding: 20
                        }
                    },
                    scales: {
                        xAxes: [{
                            stacked: true,
                            ticks: {
                                fontSize: 11,
                                maxRotation: 50,
                                minRotation: 30,
                                autoSkip: false
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Phòng thực hiện',
                                fontSize: 13,
                                fontStyle: 'bold'
                            }
                        }],
                        yAxes: [{
                            stacked: true,
                            ticks: {
                                beginAtZero: true,
                                fontSize: 12,
                                callback: function(value) {
                                    return Number.isInteger(value) ? value : '';
                                }
                            },
                            scaleLabel: {
                                display: true,
                                labelString: 'Số lượng bệnh nhân',
                                fontSize: 13,
                                fontStyle: 'bold'
                            }
                        }]
                    },
                    plugins: {
                        datalabels: {
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            },
                            color: '#fff',
                            font: { weight: 'bold', size: 11 },
                            formatter: function(value) { return value; }
                        }
                    },
                    tooltips: {
                        mode: 'index',
                        callbacks: {
                            title: function(tooltipItems, chartData) {
                                return chartData.labels[tooltipItems[0].index];
                            },
                            label: function(tooltipItem, chartData) {
                                var ds = chartData.datasets[tooltipItem.datasetIndex];
                                return ds.label + ': ' + tooltipItem.yLabel;
                            }
                        }
                    }
                }
            });
        })
        .fail(function(jqXHR, textStatus) {
            console.error('Không thể tải dữ liệu biểu đồ:', textStatus);
            // Giữ nguyên chart cũ, không cập nhật stats
        });
    }

    /* ── Tải lần đầu + auto-refresh mỗi 5 phút ── */
    $(document).ready(function() {
        loadChart();
        setInterval(loadChart, 300000);
    });
    </script>

</body>
</html>
