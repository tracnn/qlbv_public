@extends('adminlte::page')

@section('title', 'Dashboard chứng từ điện tử')

@section('content_header')
<h1>Dashboard chứng từ điện tử <small>PL02 &mdash; sức khoẻ vận hành</small></h1>
@endsection

@push('after-styles')
<style>
    .filter-row { margin-bottom: 15px; }
    .chart-box  { min-height: 350px; }
    .hang-doi-chet { color: #dd4b39; font-weight: bold; }
</style>
@endpush

@section('content')
<div class="row filter-row">
    <div class="col-md-2">
        <label>Từ ngày</label>
        {{-- 30 ngay: DUNG khoang ma CtdtDanhSach::khoangMacDinh() bu o phia server khi
             thieu tham so. De date('Y-m-01') o day la moc thu ba cua cung mot man hinh -
             ngay mung 1 thang thi o nhap bao "hom nay den hom nay" con server bao 30 ngay. --}}
        <input type="date" id="tu-ngay" class="form-control"
               value="{{ \Carbon\Carbon::now()->subDays(30)->format('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>Đến ngày</label>
        <input type="date" id="den-ngay" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-2">
        <label>Dịch vụ</label>
        <select id="dich-vu" class="form-control">
            <option value="">Tất cả</option>
            <option value="CT2025">CT2025</option>
            <option value="GBT">GBT</option>
            <option value="GCS">GCS</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>&nbsp;</label>
        <button id="btn-xem" class="btn btn-primary form-control">
            <i class="fa fa-search"></i> Xem
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Ba hàng đợi</h3></div>
            <div class="box-body" id="khoi-hang-doi">Đang tải…</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Hồ sơ theo trạng thái</h3></div>
            <div class="box-body"><div id="chart-trang-thai" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border"><h3 class="box-title">Tồn đọng</h3></div>
            <div class="box-body" id="khoi-ton-dong">Đang tải…</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-info">
            <div class="box-header with-border"><h3 class="box-title">Sản lượng theo ngày</h3></div>
            <div class="box-body"><div id="chart-san-luong" class="chart-box"></div></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="box box-danger">
            <div class="box-header with-border"><h3 class="box-title">Mã lỗi hay gặp</h3></div>
            <div class="box-body"><div id="chart-ma-loi" class="chart-box"></div></div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="box box-default">
            <div class="box-header with-border"><h3 class="box-title">Cơ sở sai nhiều nhất</h3></div>
            <div class="box-body"><div id="chart-cskcb" class="chart-box"></div></div>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script src="{{ asset('vendor/highcharts/highcharts.js') }}"></script>
<script>
    window.CTDT_DASHBOARD_CFG = {
        routes: {
            sucKhoe: '{{ route('bhyt.ctdt.dashboard.suc-khoe') }}',
            sanLuong: '{{ route('bhyt.ctdt.dashboard.san-luong') }}',
            chatLuong: '{{ route('bhyt.ctdt.dashboard.chat-luong') }}'
        }
    };
</script>
<script src="{{ asset('js/dashboard/ctdt.js') }}"></script>
@endpush
