@extends('adminlte::page')

@section('title', 'Dashboard lãnh đạo')

@section('content_header')
<h1>Dashboard lãnh đạo <small>Chỉ số điều hành theo cơ sở</small></h1>
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 320px; }
    .ld-card { min-height: 100px; }
    .ld-card .info-box-text { white-space: normal; text-overflow: clip; line-height: 1.2; }
    .ld-card .info-box-number { font-size: 22px; }
    .ld-card .ld-sub  { display: block; font-size: 12px; color: #666; }
    .ld-card .ld-note { display: block; font-size: 11px; color: #999; font-style: italic; }
    .ld-loading { text-align: center; color: #999; padding: 40px; }
    .ld-error   { text-align: center; color: #a94442; padding: 40px; }
    .ld-am      { color: #dd4b39; font-weight: bold; }
</style>
@endpush

@section('content')
<div id="lanh-dao-app">
    <div class="row filter-row">
        <div class="col-md-3 col-sm-6">
            <label for="ld-from">Từ ngày</label>
            <input type="date" id="ld-from" class="form-control" value="{{ date('Y-m-01') }}">
        </div>
        <div class="col-md-3 col-sm-6">
            <label for="ld-to">Đến ngày</label>
            <input type="date" id="ld-to" class="form-control" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-3 col-sm-6">
            <label for="ld-co-so">Cơ sở</label>
            <select id="ld-co-so" class="form-control">
                <option value="2cs" selected>Cả 2 cơ sở</option>
                <option value="hn">Hà Nội</option>
                <option value="nb">Ninh Bình</option>
            </select>
        </div>
        <div class="col-md-2 col-sm-6">
            <label>&nbsp;</label>
            <button type="button" id="ld-btn-load" class="btn btn-primary form-control">
                <i class="fa fa-search"></i> Xem
            </button>
        </div>
    </div>

    <div id="ld-error-the-so" class="alert alert-danger" style="display:none"></div>
    <div class="row" id="ld-cards">
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="ngay_dieu_tri">
                <span class="info-box-icon bg-aqua"><i class="fa fa-bed"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Ngày điều trị TB</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="cong_suat_giuong">
                <span class="info-box-icon bg-green"><i class="fa fa-percent"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Công suất giường</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="tg_kham_phut">
                <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Thời gian khám (trung vị)</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="ke_don_mua_thuoc_phut">
                <span class="info-box-icon bg-red"><i class="fa fa-medkit"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Kê đơn → mua thuốc (trung vị)</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                    <span class="ld-note">chỉ nhà thuốc BV</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="kham_moi_cu">
                <span class="info-box-icon bg-purple"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Khám mới / cũ</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
            <div class="info-box ld-card" data-the="he_ngoai">
                <span class="info-box-icon bg-teal"><i class="fa fa-scissors"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Hệ ngoại</span>
                    <span class="info-box-number ld-val">—</span>
                    <span class="ld-sub">&nbsp;</span>
                </div>
            </div>
        </div>
    </div>

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs" id="ld-tabs">
            <li class="active"><a href="#ld-tab-thanh-toan" data-toggle="tab" data-phan="thanh-toan">Thanh toán</a></li>
            <li><a href="#ld-tab-dieu-tri-giuong" data-toggle="tab" data-phan="dieu-tri-giuong">Điều trị &amp; giường</a></li>
            <li><a href="#ld-tab-kham-thuoc" data-toggle="tab" data-phan="kham-thuoc">Khám &amp; thuốc</a></li>
            <li><a href="#ld-tab-he-ngoai" data-toggle="tab" data-phan="he-ngoai">Hệ ngoại</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="ld-tab-thanh-toan">
                <div class="ld-msg" data-phan="thanh-toan"></div>
                <h4>Tỷ lệ thanh toán QR theo khoa</h4>
                <div id="ld-chart-qr" class="chart-box"></div>
                <h4>Tỷ lệ hoàn ứng online theo khoa</h4>
                <div id="ld-chart-hoan" class="chart-box"></div>
            </div>
            <div class="tab-pane" id="ld-tab-dieu-tri-giuong">
                <div class="ld-msg" data-phan="dieu-tri-giuong"></div>
                <h4>Ngày điều trị trung bình theo khoa</h4>
                <div id="ld-chart-ngay-dt" class="chart-box"></div>
                <h4>Công suất giường theo khoa</h4>
                <div id="ld-chart-cong-suat" class="chart-box"></div>
                <h4>Giường hiện tại <small>(ảnh chụp lúc tải, không theo bộ lọc ngày)</small></h4>
                <table class="table table-bordered table-hover table-condensed" id="ld-tbl-giuong">
                    <thead>
                        <tr>
                            <th>Đơn vị</th>
                            <th class="text-right">Giường phê duyệt</th>
                            <th class="text-right">Đang nằm</th>
                            <th class="text-right">Còn trống</th>
                            <th class="text-right">Công suất tức thời</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="tab-pane" id="ld-tab-kham-thuoc">
                <div class="ld-msg" data-phan="kham-thuoc"></div>
                <h4>Thời gian khám theo giờ tiếp đón <small>(trung vị, phút; cột đầu = đến 5h, cột cuối = từ 17h)</small></h4>
                <div id="ld-chart-tg-kham" class="chart-box"></div>
                <h4>Phân bố thời gian kê đơn → mua thuốc</h4>
                <div id="ld-chart-ke-don" class="chart-box"></div>
                <h4>Khám mới / cũ theo cơ sở</h4>
                <div id="ld-chart-moi-cu" class="chart-box"></div>
            </div>
            <div class="tab-pane" id="ld-tab-he-ngoai">
                <div class="ld-msg" data-phan="he-ngoai"></div>
                <h4>Y lệnh phẫu thuật theo khoa ngoại</h4>
                <div id="ld-chart-pt" class="chart-box"></div>
                <h4>Can thiệp tim mạch theo phòng</h4>
                <div id="ld-chart-can-thiep" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/modules/exporting.js') }}"></script>
<script>window.LANH_DAO_CFG = { url: '{{ url('dashboard/lanh-dao') }}' };</script>
<script src="{{ asset('js/dashboard/lanh-dao.js') }}"></script>
@endpush
