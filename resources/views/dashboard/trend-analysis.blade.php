@extends('adminlte::page')

@section('title', 'Xu hướng & Vận hành')

@section('content_header')
<h1>Xu hướng & Vận hành <small>Dashboard</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.trends' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .kpi-card   { font-size: 2em; font-weight: bold; text-align: center; padding: 20px; }
    .alert-card { padding: 15px; border-radius: 4px; margin-bottom: 15px; }
    .alert-overload  { background: #f2dede; border: 1px solid #ebccd1; color: #a94442; }
    .alert-underload { background: #fcf8e3; border: 1px solid #faebcc; color: #8a6d3b; }
    .alert-normal    { background: #dff0d8; border: 1px solid #d6e9c6; color: #3c763d; }
</style>
@endpush

@section('content')
{{-- Alert quá tải --}}
<div id="overload-alert-box"></div>

{{-- Bộ lọc --}}
<div class="row filter-row">
    <div class="col-md-3">
        <label>Từ ngày</label>
        <input type="date" id="from-date" class="form-control" value="{{ date('Y-m-01') }}">
    </div>
    <div class="col-md-3">
        <label>Đến ngày</label>
        <input type="date" id="to-date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>Chỉ số</label>
        <select id="metric" class="form-control">
            <option value="examinations">Lượt khám</option>
            <option value="revenue">Doanh thu</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-load" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

{{-- Xu hướng --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Biểu đồ xu hướng</h3>
        <div class="box-tools">
            <div class="btn-group" data-toggle="buttons">
                <label class="btn btn-default btn-sm active">
                    <input type="radio" name="mode" value="daily" checked> Theo ngày
                </label>
                <label class="btn btn-default btn-sm">
                    <input type="radio" name="mode" value="monthly"> Theo tháng
                </label>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div id="chart-trend" class="chart-box"></div>
    </div>
</div>

{{-- BN/giờ --}}
<div class="row">
    <div class="col-md-4">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">BN/giờ trung bình</h3>
            </div>
            <div class="box-body">
                <div id="kpi-avg-per-hour" class="kpi-card text-info">--</div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Phân bố BN theo khung giờ</h3>
            </div>
            <div class="box-body">
                <div id="chart-by-hour" class="chart-box"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/modules/exporting.js') }}"></script>
<script>
window.TREND_CFG = {
    routes: {
        trendChart:      '{{ route("dashboard.trends.chart") }}',
        patientsPerHour: '{{ route("dashboard.trends.patients-per-hour") }}',
        overloadAlert:   '{{ route("dashboard.trends.overload-alert") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/trend-charts.js') }}"></script>
@endpush
