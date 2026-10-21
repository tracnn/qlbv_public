@extends('adminlte::page')

@section('title', 'Dashboard lãnh đạo')

@section('content_header')
<h1>Dashboard lãnh đạo <small>Chỉ số điều hành theo cơ sở</small></h1>
@endsection

@push('after-styles')
<style>
    .filter-row  { margin-bottom: 15px; }
    .chart-box   { min-height: 350px; }
    .small-box h3 { font-size: 30px; white-space: nowrap; }
    .small-box .small-box-footer { cursor: default; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-left: 10px; padding-right: 10px; }
    .small-box .ld-note { opacity: .85; }
    #ld-tabs { margin-bottom: 15px; }
    .ld-loading { text-align: center; color: #999; padding: 40px; }
    .ld-am      { color: #dd4b39; font-weight: bold; }
</style>
@endpush

@section('content')
<div id="lanh-dao-app">
    <div class="row filter-row">
        <div class="col-md-2">
            <label for="ld-from">Từ ngày</label>
            <input type="date" id="ld-from" class="form-control" value="{{ date('Y-m-01') }}">
        </div>
        <div class="col-md-2">
            <label for="ld-to">Đến ngày</label>
            <input type="date" id="ld-to" class="form-control" value="{{ date('Y-m-d') }}">
        </div>
        @include('partials.ma_cskcb', ['danhSachCoSo' => $danhSachCoSo, 'colClass' => 'col-md-2', 'formGroup' => false])
        <div class="col-md-2">
            <label>&nbsp;</label>
            <button type="button" id="ld-btn-load" class="btn btn-primary form-control">
                <i class="fa fa-search"></i> Xem
            </button>
        </div>
    </div>

    {{-- KPI --}}
    <div id="ld-error-the-so" class="alert alert-danger" style="display:none"></div>
    <div class="row" id="ld-cards">
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-aqua ld-card" data-the="ngay_dieu_tri">
                <div class="inner"><h3 class="ld-val">-</h3><p>Ngày điều trị TB</p></div>
                <div class="icon"><i class="fa fa-bed"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-green ld-card" data-the="cong_suat_giuong">
                <div class="inner"><h3 class="ld-val">-</h3><p>Công suất giường</p></div>
                <div class="icon"><i class="fa fa-percent"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-yellow ld-card" data-the="tg_kham_phut">
                <div class="inner"><h3 class="ld-val">-</h3><p>Thời gian khám (trung vị)</p></div>
                <div class="icon"><i class="fa fa-clock-o"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-red ld-card" data-the="ke_don_mua_thuoc_phut">
                <div class="inner"><h3 class="ld-val">-</h3><p>Kê đơn → mua thuốc (nhà thuốc BV)</p></div>
                <div class="icon"><i class="fa fa-medkit"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-purple ld-card" data-the="kham_moi_cu">
                <div class="inner"><h3 class="ld-val">-</h3><p>Khám mới / cũ <span class="ld-note ld-dyn"></span></p></div>
                <div class="icon"><i class="fa fa-users"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="small-box bg-teal ld-card" data-the="he_ngoai">
                <div class="inner"><h3 class="ld-val">-</h3><p>Hệ ngoại: y lệnh PT · ca PT · can thiệp TM</p></div>
                <div class="icon"><i class="fa fa-scissors"></i></div>
                <div class="small-box-footer"><span class="ld-sub">&nbsp;</span></div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs" id="ld-tabs">
        <li class="active"><a href="#ld-tab-thanh-toan" data-toggle="tab" data-phan="thanh-toan">Thanh toán</a></li>
        <li><a href="#ld-tab-dieu-tri-giuong" data-toggle="tab" data-phan="dieu-tri-giuong">Điều trị &amp; giường</a></li>
        <li><a href="#ld-tab-kham-thuoc" data-toggle="tab" data-phan="kham-thuoc">Khám &amp; thuốc</a></li>
        <li><a href="#ld-tab-he-ngoai" data-toggle="tab" data-phan="he-ngoai">Hệ ngoại</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="ld-tab-thanh-toan">
            <div class="ld-msg" data-phan="thanh-toan"></div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title">Tỷ lệ thanh toán QR theo khoa</h3></div>
                        <div class="box-body"><div id="ld-chart-qr" class="chart-box"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-success">
                        <div class="box-header with-border"><h3 class="box-title">Tỷ lệ hoàn ứng online theo khoa</h3></div>
                        <div class="box-body"><div id="ld-chart-hoan" class="chart-box"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="ld-tab-dieu-tri-giuong">
            <div class="ld-msg" data-phan="dieu-tri-giuong"></div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title">Ngày điều trị trung bình theo khoa</h3></div>
                        <div class="box-body"><div id="ld-chart-ngay-dt" class="chart-box"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-danger">
                        <div class="box-header with-border"><h3 class="box-title">Công suất giường theo khoa</h3></div>
                        <div class="box-body"><div id="ld-chart-cong-suat" class="chart-box"></div></div>
                    </div>
                </div>
            </div>
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Giường hiện tại</h3>
                    <small class="text-muted">(Ảnh chụp lúc tải — không phụ thuộc khoảng ngày đã chọn)</small>
                </div>
                <div class="box-body table-responsive">
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
            </div>
        </div>

        <div class="tab-pane" id="ld-tab-kham-thuoc">
            <div class="ld-msg" data-phan="kham-thuoc"></div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-warning">
                        <div class="box-header with-border">
                            <h3 class="box-title">Thời gian khám theo giờ tiếp đón</h3>
                            <small class="text-muted">(Trung vị, phút — cột đầu gộp đến 5h, cột cuối gộp từ 17h)</small>
                        </div>
                        <div class="box-body"><div id="ld-chart-tg-kham" class="chart-box"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-danger">
                        <div class="box-header with-border"><h3 class="box-title">Phân bố thời gian kê đơn → mua thuốc</h3></div>
                        <div class="box-body"><div id="ld-chart-ke-don" class="chart-box"></div></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-info">
                        <div class="box-header with-border"><h3 class="box-title">Khám mới / cũ theo cơ sở</h3></div>
                        <div class="box-body"><div id="ld-chart-moi-cu" class="chart-box"></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="ld-tab-he-ngoai">
            <div class="ld-msg" data-phan="he-ngoai"></div>
            <div class="row">
                <div class="col-md-6">
                    <div class="box box-primary">
                        <div class="box-header with-border"><h3 class="box-title">Y lệnh phẫu thuật theo khoa ngoại</h3></div>
                        <div class="box-body"><div id="ld-chart-pt" class="chart-box"></div></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="box box-danger">
                        <div class="box-header with-border"><h3 class="box-title">Can thiệp tim mạch theo phòng</h3></div>
                        <div class="box-body"><div id="ld-chart-can-thiep" class="chart-box"></div></div>
                    </div>
                </div>
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
