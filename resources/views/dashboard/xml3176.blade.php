@extends('adminlte::page')

@section('title', 'Dashboard XML3176')

@section('content_header')
<h1>Dashboard kiểm tra lỗi XML3176 <small>Tổng quan &amp; tồn đọng</small></h1>
@endsection

@push('after-styles')
<style>
    .filter-row  { margin-bottom: 15px; }
    .chart-box   { min-height: 350px; }
    .small-box h3 { font-size: 30px; }
    .small-box .kpi-link { cursor: pointer; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-2">
        <label>Loại ngày</label>
        <select id="date-type" class="form-control">
            <option value="date_out">Ngày ra viện</option>
            <option value="date_in">Ngày vào viện</option>
            <option value="date_payment">Ngày thanh toán</option>
            <option value="date_create">Ngày tạo</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>Từ ngày</label>
        <input type="date" id="from-date" class="form-control" value="{{ date('Y-m-01') }}">
    </div>
    <div class="col-md-2">
        <label>Đến ngày</label>
        <input type="date" id="to-date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-load" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

{{-- KPI --}}
<div class="row" id="kpi-row">
    <div class="col-lg-2 col-xs-6">
        <div class="small-box bg-blue">
            <div class="inner"><h3 id="kpi-total">-</h3><p>Tổng hồ sơ</p></div>
            <a href="#" class="small-box-footer kpi-link" data-kpi="total">Xem danh sách <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-xs-6">
        <div class="small-box bg-red">
            <div class="inner"><h3 id="kpi-critical">-</h3><p>Lỗi nghiêm trọng</p></div>
            <a href="#" class="small-box-footer kpi-link" data-kpi="critical">Xem danh sách <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-2 col-xs-6">
        <div class="small-box bg-yellow">
            <div class="inner"><h3 id="kpi-hein-card">-</h3><p>Lỗi thẻ BHYT</p></div>
            <a href="#" class="small-box-footer kpi-link" data-kpi="hein_card">Xem danh sách <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-orange">
            <div class="inner"><h3 id="kpi-blocked">-</h3><p>Chi phí BHYT bị treo</p></div>
            <a href="#" class="small-box-footer kpi-link" data-kpi="blocked">Xem danh sách <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
            <div class="inner"><h3 id="kpi-submitted">-</h3><p>Đã gửi BHXH</p></div>
            <a href="#" class="small-box-footer kpi-link" data-kpi="submitted">Xem danh sách <i class="fa fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Phễu xử lý hồ sơ</h3></div>
            <div class="box-body"><div id="chart-funnel" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Top 15 mã lỗi (Pareto)</h3></div>
            <div class="box-body"><div id="chart-pareto" class="chart-box"></div></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Tồn đọng theo tuổi hồ sơ (chưa gửi)</h3>
                <small class="text-muted">(Không phụ thuộc khoảng ngày đã chọn — luôn tính theo ngày ra viện so với hôm nay)</small>
            </div>
            <div class="box-body"><div id="chart-aging" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Hồ sơ lỗi nghiêm trọng theo khoa</h3></div>
            <div class="box-body"><div id="chart-department" class="chart-box"></div></div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/modules/exporting.js') }}"></script>
<script>
window.XML3176_CFG = {
    routes: {
        overview:     '{{ route("dashboard.xml3176.overview") }}',
        topErrors:    '{{ route("dashboard.xml3176.top-errors") }}',
        aging:        '{{ route("dashboard.xml3176.aging") }}',
        byDepartment: '{{ route("dashboard.xml3176.by-department") }}',
        listScreen:   '{{ route("bhyt.xml3176.index") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/xml3176.js') }}"></script>
@endpush
