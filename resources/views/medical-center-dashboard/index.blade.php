<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <titleDashboard</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    
    <!-- Bootstrap 3.3.7 -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/bootstrap/dist/css/bootstrap.min.css') }}">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/font-awesome/css/font-awesome.min.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/Ionicons/css/ionicons.min.css') }}">
    
    <style>
        body {
            background: #0a1929;
            color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 10px;
        }
        
        .dashboard-container {
            max-width: 1920px;
            margin: 0 auto;
            background: #0a1929;
        }
        
        .dashboard-header {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #ffd700;
            margin-bottom: 15px;
        }
        
        .dashboard-header h1 {
            color: #fff;
            font-size: 36px;
            font-weight: bold;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .dashboard-section {
            background: rgba(26, 45, 70, 0.8);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .section-title {
            color: #4fc3f7;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #4fc3f7;
            padding-bottom: 5px;
        }
        
        .stat-box {
            background: rgba(15, 32, 55, 0.6);
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
            border-left: 3px solid #4fc3f7;
        }
        
        .stat-label {
            color: #b0bec5;
            font-size: 14px;
            margin-bottom: 3px;
        }
        
        .stat-value {
            color: #4fc3f7;
            font-size: 32px;
            font-weight: bold;
            margin: 2px 0;
            line-height: 1.2;
        }
        
        .stat-percent {
            color: #81c784;
            font-size: 14px;
            margin-left: 5px;
        }
        
        .stat-change {
            display: inline-block;
            margin-left: 5px;
            font-size: 14px;
        }
        
        .stat-change.up {
            color: #81c784;
        }
        
        .stat-change.down {
            color: #e57373;
        }
        
        .stat-change i {
            margin-right: 2px;
        }
        
        .row-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .col-stat {
            flex: 1;
            min-width: 120px;
        }
        
        .status-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .status-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-item:last-child {
            border-bottom: none;
        }
        
        .status-label {
            color: #b0bec5;
            font-size: 14px;
        }
        
        .status-value {
            color: #fff;
            font-weight: bold;
        }
        
        .pie-chart-container {
            width: 100px;
            height: 100px;
            margin: 5px auto;
        }
        
        .date-display {
            text-align: center;
            color: #b0bec5;
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 15px;
            right: 15px;
            background: #4fc3f7;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            background: #29b6f6;
            transform: rotate(180deg);
        }
        
        .loading {
            text-align: center;
            padding: 10px;
            color: #4fc3f7;
        }
        
        .loading p {
            margin-top: 5px;
            font-size: 14px;
        }
        
        .section-loading {
            min-height: 60px;
        }
        
        .row {
            margin-left: -5px;
            margin-right: -5px;
        }
        
        .row > [class*="col-"] {
            padding-left: 5px;
            padding-right: 5px;
        }
        
        @media (max-width: 768px) {
            .col-stat {
                min-width: 100%;
            }
            body {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>{{config('organization.organization_name')}}</h1>
        </div>
        
        <div id="dashboard-content">
            <div class="row">
                <!-- Khám bệnh -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-kham-benh">
                        <div class="section-title">Khám bệnh</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Nội trú -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-noi-tru">
                        <div class="section-title">Nội trú</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                    
                    <!-- Ngoại trú -->
                    <div class="dashboard-section" id="section-ngoai-tru" style="margin-top: 10px;">
                        <div class="section-title">Ngoại trú</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Cận lâm sàng & Phẫu thuật -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-can-lam-sang">
                        <div class="section-title">Cận lâm sàng</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                    
                    <div class="dashboard-section" id="section-phau-thuat-thu-thuat" style="margin-top: 10px;">
                        <div class="section-title">Phẫu thuật - Thủ thuật</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row" style="margin-top: 10px;">
                <!-- Thời gian chờ khám -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-thoi-gian-cho-kham">
                        <div class="section-title">Thời gian chờ khám (phút)</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Thời gian khám bệnh trung bình -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-thoi-gian-kham-trung-binh">
                        <div class="section-title">Thời gian khám TB theo đối tượng (phút)</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Thời gian chờ khác -->
                <div class="col-md-4">
                    <div class="dashboard-section" id="section-thoi-gian-cho-khac">
                        <div class="section-title">Thời gian chờ khác (phút)</div>
                        <div class="loading">
                            <i class="fa fa-spinner fa-spin"></i>
                            <p>Đang tải...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <button class="refresh-btn" onclick="refreshAll()" title="Làm mới dữ liệu">
        <i class="fa fa-refresh"></i>
    </button>
    
    <!-- jQuery -->
    <script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
    <!-- Chart.js -->
    <script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
    
    <script>
        let refreshInterval;
        let currentDate = new Date().toISOString().split('T')[0];
        
        // Load từng phần dữ liệu riêng biệt
        function loadDashboardData(date) {
            if (!date) {
                date = currentDate;
            } else {
                currentDate = date;
            }
            
            // Gọi tất cả các API cùng lúc (parallel requests)
            loadKhamBenh(date);
            loadNoiTru(date);
            loadNgoaiTru(date);
            loadCanLamSang(date);
            loadPhauThuatThuThuat(date);
            loadThoiGianChoKham(date);
            loadThoiGianKhamTrungBinh(date);
            loadThoiGianChoKhac(date);
        }
        
        function loadKhamBenh(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.kham-benh") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderKhamBenh(data);
                },
                error: function(xhr, status, error) {
                    $('#section-kham-benh').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadNoiTru(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.noi-tru") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderNoiTru(data);
                },
                error: function(xhr, status, error) {
                    $('#section-noi-tru').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadNgoaiTru(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.ngoai-tru") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderNgoaiTru(data);
                },
                error: function(xhr, status, error) {
                    $('#section-ngoai-tru').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadCanLamSang(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.can-lam-sang") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderCanLamSang(data);
                },
                error: function(xhr, status, error) {
                    $('#section-can-lam-sang').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadPhauThuatThuThuat(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.phau-thuat-thu-thuat") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderPhauThuatThuThuat(data);
                },
                error: function(xhr, status, error) {
                    $('#section-phau-thuat-thu-thuat').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadThoiGianChoKham(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.thoi-gian-cho-kham") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderThoiGianChoKham(data);
                },
                error: function(xhr, status, error) {
                    $('#section-thoi-gian-cho-kham').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadThoiGianKhamTrungBinh(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.thoi-gian-kham-trung-binh") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderThoiGianKhamTrungBinh(data);
                },
                error: function(xhr, status, error) {
                    $('#section-thoi-gian-kham-trung-binh').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        function loadThoiGianChoKhac(date) {
            $.ajax({
                url: '{{ route("medical-center-dashboard.thoi-gian-cho-khac") }}',
                type: 'GET',
                data: { date: date },
                dataType: 'json',
                success: function(data) {
                    renderThoiGianChoKhac(data);
                },
                error: function(xhr, status, error) {
                    $('#section-thoi-gian-cho-khac').html('<div class="loading"><p style="color: #e57373;">Lỗi khi tải dữ liệu</p></div>');
                }
            });
        }
        
        // Render functions cho từng section
        function renderKhamBenh(data) {
            let html = `
                <div class="section-title">Khám bệnh</div>
                
                <div class="stat-box">
                    <div class="stat-label">Tổng lượt khám</div>
                    <div class="stat-value">
                        ${formatNumber(data.total)}
                        ${getChangeIndicator(data.total_change)}
                    </div>
                </div>
                
                <div class="row-stats">
                    <div class="col-stat">
                        <div class="stat-box">
                            <div class="stat-label">BHYT</div>
                            <div class="stat-value">${formatNumber(data.bhyt)}</div>
                            <div class="stat-percent">${data.bhyt_percent}%</div>
                        </div>
                    </div>
                    <div class="col-stat">
                        <div class="stat-box">
                            <div class="stat-label">Viện phí</div>
                            <div class="stat-value">${formatNumber(data.vien_phi)}</div>
                            <div class="stat-percent">${data.vien_phi_percent}%</div>
                        </div>
                    </div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Cấp cứu</div>
                    <div class="stat-value">${formatNumber(data.cap_cuu)}</div>
                </div>
                
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-label">Xử trí</span>
                        <span class="status-value">${formatNumber(data.trang_thai.xu_tri)} (${data.trang_thai.xu_tri_percent}%)</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chưa chỉ định</span>
                        <span class="status-value">${formatNumber(data.trang_thai.chua_chi_dinh)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Nhập viện</span>
                        <span class="status-value">${formatNumber(data.trang_thai.nhap_vien)} (${data.trang_thai.nhap_vien_percent}%)</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Đã chỉ định</span>
                        <span class="status-value">${formatNumber(data.trang_thai.da_chi_dinh)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chuyển viện</span>
                        <span class="status-value">${formatNumber(data.trang_thai.chuyen_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chờ kết luận</span>
                        <span class="status-value">${formatNumber(data.trang_thai.cho_ket_luan)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Cấp toa về</span>
                        <span class="status-value">${formatNumber(data.trang_thai.cap_toa_ve)} (${data.trang_thai.cap_toa_ve_percent}%)</span>
                    </li>
                </ul>
            `;
            $('#section-kham-benh').html(html);
        }
        
        function renderNoiTru(data) {
            let html = `
                <div class="section-title">Nội trú</div>
                
                <div class="stat-box">
                    <div class="stat-label">Nhập viện</div>
                    <div class="stat-value">
                        ${formatNumber(data.benh_nhan_nhap_vien)}
                        ${getChangeIndicator(data.benh_nhan_nhap_vien_change)}
                    </div>
                    <div class="stat-label">Đang điều trị</div>
                    <div class="stat-value">${formatNumber(data.dang_dieu_tri)}</div>
                </div>
                
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-label">Chờ nhập viện</span>
                        <span class="status-value">${formatNumber(data.cho_nhap_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Xuất viện</span>
                        <span class="status-value">${formatNumber(data.xuat_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chuyển viện</span>
                        <span class="status-value">${formatNumber(data.chuyen_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Tử vong</span>
                        <span class="status-value">${formatNumber(data.tu_vong)}</span>
                    </li>
                </ul>
            `;
            $('#section-noi-tru').html(html);
        }
        
        function renderNgoaiTru(data) {
            let html = `
                <div class="section-title">Ngoại trú</div>
                
                <div class="stat-box">
                    <div class="stat-label">Đang điều trị</div>
                    <div class="stat-value">${formatNumber(data.dang_dieu_tri)}</div>
                </div>
                
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-label">Chờ nhập viện</span>
                        <span class="status-value">${formatNumber(data.cho_nhap_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Xuất viện</span>
                        <span class="status-value">${formatNumber(data.xuat_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chuyển viện</span>
                        <span class="status-value">${formatNumber(data.chuyen_vien)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Tử vong</span>
                        <span class="status-value">${formatNumber(data.tu_vong)}</span>
                    </li>
                </ul>
            `;
            $('#section-ngoai-tru').html(html);
        }
        
        function renderCanLamSang(data) {
            let html = `
                <div class="section-title">Cận lâm sàng</div>
                
                <div class="stat-box">
                    <div class="stat-label">Xét nghiệm</div>
                    <div class="stat-value">${formatNumber(data.xet_nghiem.total)}</div>
                    <ul class="status-list" style="margin-top: 5px;">
                        <li class="status-item">
                            <span class="status-label">Chưa làm</span>
                            <span class="status-value">${formatNumber(data.xet_nghiem.chua_lam)}</span>
                        </li>
                        <li class="status-item">
                            <span class="status-label">Đang làm</span>
                            <span class="status-value">${formatNumber(data.xet_nghiem.dang_lam)}</span>
                        </li>
                        <li class="status-item">
                            <span class="status-label">Đã làm</span>
                            <span class="status-value">${formatNumber(data.xet_nghiem.da_lam)}</span>
                        </li>
                    </ul>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Chẩn đoán HA</div>
                    <div class="stat-value">${formatNumber(data.cdha.total)}</div>
                    <ul class="status-list" style="margin-top: 5px;">
                        <li class="status-item">
                            <span class="status-label">Chưa làm</span>
                            <span class="status-value">${formatNumber(data.cdha.chua_lam)}</span>
                        </li>
                        <li class="status-item">
                            <span class="status-label">Đang làm</span>
                            <span class="status-value">${formatNumber(data.cdha.dang_lam)}</span>
                        </li>
                        <li class="status-item">
                            <span class="status-label">Đã làm</span>
                            <span class="status-value">${formatNumber(data.cdha.da_lam)}</span>
                        </li>
                    </ul>
                </div>
            `;
            $('#section-can-lam-sang').html(html);
        }
        
        function renderPhauThuatThuThuat(data) {
            let html = `
                <div class="section-title">Phẫu thuật - Thủ thuật</div>
                
                <div class="row-stats">
                    <div class="col-stat">
                        <div class="stat-box">
                            <div class="stat-label">Phẫu thuật</div>
                            <div class="stat-value">${formatNumber(data.phau_thuat)}</div>
                        </div>
                    </div>
                    <div class="col-stat">
                        <div class="stat-box">
                            <div class="stat-label">Thủ thuật</div>
                            <div class="stat-value">${formatNumber(data.thu_thuat)}</div>
                        </div>
                    </div>
                </div>
            `;
            $('#section-phau-thuat-thu-thuat').html(html);
        }
        
        function renderThoiGianChoKham(data) {
            let html = `
                <div class="section-title">Thời gian chờ khám (phút)</div>
                <div class="date-display">${formatDate(new Date())}</div>
                
                <div class="stat-box">
                    <div class="stat-label">Trung bình</div>
                    <div class="stat-value">${formatNumber(data.trung_binh)}</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Lâu nhất</div>
                    <div class="stat-value">${formatNumber(data.lau_nhat)}</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Nhanh nhất</div>
                    <div class="stat-value">${formatNumber(data.nhanh_nhat)}</div>
                </div>
            `;
            $('#section-thoi-gian-cho-kham').html(html);
        }
        
        function renderThoiGianKhamTrungBinh(data) {
            let html = `
                <div class="section-title">Thời gian khám TB theo đối tượng (phút)</div>
                
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-label">Tất cả đối tượng</span>
                        <span class="status-value">${formatNumber(data.tat_ca)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Chỉ khám bệnh</span>
                        <span class="status-value">${formatNumber(data.chi_kham)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Khám + XN</span>
                        <span class="status-value">${formatNumber(data.kham_xn)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Khám + XN + CĐHA</span>
                        <span class="status-value">${formatNumber(data.kham_xn_cdha)}</span>
                    </li>
                </ul>
            `;
            $('#section-thoi-gian-kham-trung-binh').html(html);
        }
        
        function renderThoiGianChoKhac(data) {
            let html = `
                <div class="section-title">Thời gian chờ khác (phút)</div>
                
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-label">Xét nghiệm</span>
                        <span class="status-value">${formatNumber(data.xet_nghiem)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">CĐHA</span>
                        <span class="status-value">${formatNumber(data.cdha)}</span>
                    </li>
                    <li class="status-item">
                        <span class="status-label">Lấy thuốc</span>
                        <span class="status-value">${formatNumber(data.lay_thuoc)}</span>
                    </li>
                </ul>
            `;
            $('#section-thoi-gian-cho-khac').html(html);
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function formatDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}-${month} ${hours}.${minutes}.${year}`;
        }
        
        function getChangeIndicator(change) {
            if (change === null || change === undefined) return '';
            const isPositive = change >= 0;
            const icon = isPositive ? 'fa-arrow-up' : 'fa-arrow-down';
            const className = isPositive ? 'up' : 'down';
            const sign = isPositive ? '+' : '';
            return `<span class="stat-change ${className}"><i class="fa ${icon}"></i>${sign}${change}%</span>`;
        }
        
        // Load data on page load
        $(document).ready(function() {
            loadDashboardData();
            
            // Auto refresh every 5 minutes
            refreshInterval = setInterval(function() {
                loadDashboardData();
            }, 300000); // 5 minutes
        });
        
        // Hàm refresh tất cả (cho nút refresh)
        function refreshAll() {
            loadDashboardData();
        }
        
        // Clean up interval on page unload
        $(window).on('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>

