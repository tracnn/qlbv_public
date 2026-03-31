@extends('adminlte::page')

@section('title', 'Thống kê theo bác sĩ')

@section('content_header')
<h1>Thống kê theo bác sĩ <small>Dashboard</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.doctor-stats' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box { min-height: 350px; }
    .tab-content { padding-top: 15px; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Từ ngày</label>
            <input type="date" id="from-date" class="form-control"
                   value="{{ date('Y-m-01') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>Đến ngày</label>
            <input type="date" id="to-date" class="form-control"
                   value="{{ date('Y-m-d') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="form-group">
            <label>&nbsp;</label>
            <button id="btn-load" class="btn btn-primary form-control">
                <i class="fa fa-search"></i> Xem
            </button>
        </div>
    </div>
</div>

{{-- Tabs --}}
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-exam" data-toggle="tab">Lượt khám</a></li>
        <li><a href="#tab-revenue" data-toggle="tab">Doanh thu</a></li>
        <li><a href="#tab-surgery" data-toggle="tab">Phẫu thuật</a></li>
    </ul>
    <div class="tab-content">
        {{-- Tab: Lượt khám --}}
        <div class="tab-pane active" id="tab-exam">
            <div id="chart-exam" class="chart-box"></div>
            <div style="margin-top:15px">
                <table class="table table-bordered table-hover table-condensed" id="tbl-exam" width="100%">
                    <thead>
                        <tr>
                            <th>Bác sĩ</th>
                            <th>Lượt khám</th>
                            <th>BN</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        {{-- Tab: Doanh thu --}}
        <div class="tab-pane" id="tab-revenue">
            <div id="chart-revenue" class="chart-box"></div>
            <div style="margin-top:15px">
                <table class="table table-bordered table-hover table-condensed" id="tbl-revenue" width="100%">
                    <thead>
                        <tr>
                            <th>Bác sĩ</th>
                            <th>Doanh thu</th>
                            <th>BN</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        {{-- Tab: Phẫu thuật --}}
        <div class="tab-pane" id="tab-surgery">
            <div id="chart-surgery" class="chart-box"></div>
            <div style="margin-top:15px">
                <table class="table table-bordered table-hover table-condensed" id="tbl-surgery" width="100%">
                    <thead>
                        <tr>
                            <th>PTV chính</th>
                            <th>Số ca</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/modules/exporting.js') }}"></script>
<script>
window.DOCTOR_STATS_CFG = {
    routes: {
        examinations: '{{ route("dashboard.doctor-stats.examinations") }}',
        revenue:      '{{ route("dashboard.doctor-stats.revenue") }}',
        surgeries:    '{{ route("dashboard.doctor-stats.surgeries") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/doctor-stats.js') }}"></script>
@endpush
