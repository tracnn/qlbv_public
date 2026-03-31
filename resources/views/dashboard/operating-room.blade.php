@extends('adminlte::page')

@section('title', 'Công suất phòng mổ')

@section('content_header')
<h1>Công suất phòng mổ <small>Chỉ phẫu thuật</small></h1>
{{-- Breadcrumbs nếu cần: đăng ký 'dashboard.operating-room' trong routes/breadcrumbs.php --}}
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .status-overload  { color: #a94442; font-weight: bold; }
    .status-optimal   { color: #3c763d; font-weight: bold; }
    .status-underload { color: #8a6d3b; font-weight: bold; }
</style>
@endpush

@section('content')
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
        <label>&nbsp;</label>
        <button id="btn-load" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

{{-- Heatmap: ca mổ/phòng/ngày --}}
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Số ca phẫu thuật theo phòng / ngày</h3>
    </div>
    <div class="box-body">
        <div id="chart-heatmap" class="chart-box"></div>
    </div>
</div>

{{-- Bar chart: % công suất --}}
<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">% Công suất sử dụng phòng mổ (mặc định 8h/ngày)</h3>
    </div>
    <div class="box-body">
        <div id="chart-utilization" class="chart-box"></div>
        <table class="table table-bordered table-hover table-condensed" id="tbl-utilization">
            <thead>
                <tr>
                    <th>Phòng mổ</th>
                    <th>Số ca</th>
                    <th>Tổng phút sử dụng</th>
                    <th>Ngày làm việc</th>
                    <th>% Công suất</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script src="{{ asset('vendor/highcharts/modules/exporting.js') }}"></script>
<script>
window.OR_CFG = {
    routes: {
        casesPerRoom: '{{ route("dashboard.operating-room.cases-per-room") }}',
        utilization:  '{{ route("dashboard.operating-room.utilization") }}'
    }
};
</script>
<script src="{{ asset('js/dashboard/operating-room.js') }}"></script>
@endpush
