@extends('adminlte::page')

@section('title', __('manager.backend.labels.title'))

@section('content_header')
<h1>
    Dashboard
    <small>Control panel</small>
</h1>
<!-- <button id="btn-refresh-dashboard" class="btn btn-primary btn-sm">
    <i class="fa fa-refresh"></i>
</button> -->
{{ Breadcrumbs::render('dashboard') }}

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

    #refresh-after .form-control {
        font-size: 12px;
        height: 28px;
        padding: 2px 5px;
    }
    #refresh-after #dateRangePicker {
        min-width: 220px;
        font-size: 12px;
        padding: 2px 5px;
    }
</style>
<link rel="stylesheet" type="text/css" href="{{ asset('css/daterangepicker.css') }}" />
@endpush

@section('content')
@if(auth()->user()->hasRole('dashboard'))
<div class="panel panel-default">
    <div class="panel-body">
      <div class="row">

        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3 id="sum_doanhthu">0</h3>
              <p>Doanh thu</p>
            </div>
            <div class="icon">
              <i class="ion ion-bag"></i>
            </div>
          </div>
        </div>
      
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3 id="sum_treatment">0</h3>
              <p>BN đăng ký khám</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3 id="sum_newpatient">0</h3>
              <p>BN khám lần đầu</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3 id="sum_noitru">0</h3>
              <p>VV: Nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3 id="sum_chuyenvien">0</h3>
              <p>Chuyển viện</p>
            </div>
            <div class="icon">
              <i class="ion ion-card"></i>
            </div>
          </div>
        </div>

      </div>

    <!-- Dòng thứ 2 -->
    <div class="row">
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3 id="sum_ravien">0</h3>
              <p>BN ra viện</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
      
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3 id="sum_ravien_noitru">0</h3>
              <p>RV: nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3 id="sum_ravien_ngoaitru">0</h3>
              <p>RV: ngoại trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3 id="sum_ravien_kham">0</h3>
              <p>Kết thúc khám</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3 id="sum_phauthuat">0</h3>
              <p>Phẫu thuật</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-green">
            <div class="inner">
              <h3 id="sum_thuthuat">0</h3>
              <p>Thủ thuật</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
    </div>
    <!-- Dòng thứ 2 -->

    <div class="row">
        <div class="col-lg-6 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_buongbenh" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_noitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(config("organization.is_bieudo_dieutringoaitru"))
    <div class="row">
        <div class="col-lg-6 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_buongbenh_dieutringoaitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_vaovien_dieutringoaitru" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_exam_paraclinical_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center">
                <div class="tab-content no-padding">
                    <div id="chart_diagnotic_imaging_time" style="width: 100%; height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>

      <div class="row">
        <div class="col-lg-12 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_kham_by_room" style="width:100%; height:500px;"></div>
                    </div>
                </div>
            </div>
        </div>

      </div>

      <div class="row">
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_doanhthu" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_treatment" style="width: 100%; height: 300px;"></div>
                </div>
            </div>
        </div>

      </div>

    <div class="row">
        <div class="col-lg-12 connectedSortable">
          <div class="row">
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_transaction_types" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_pay_forms" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_treatment_types" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 connectedSortable">
                <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                    <div class="tab-content no-padding">
                        <div id="chart_cashiers" style="width: 100%; height: 400px;"></div>
                    </div>
                </div>
            </div>
          </div>  
        </div>
    </div>
    
    <!-- CLS 1 -->
      <div class="row">
        <div class="col-lg-4 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_kham" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_xetnghiem" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_cdha" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>

      </div>

      <!-- CLS 2 -->
      <div class="row">
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_tdcn" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_gpb" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_ns" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding">
                    <div id="chart_sa" style="width: 100%; height: 200px;"></div>
                </div>
            </div>
        </div>

      </div>      
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
    <input type="text" id="dateRangePicker" class="form-control" style="width: 180px;" />
</div>

@endif

@stop

@push('after-scripts')
<script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
<script src="{{ asset('vendor/numeral/locales.js') }}"></script>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-3d.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>

<script type="text/javascript" src="{{ asset('js/moment.min.js') }}"></script>
<!-- Include daterangepicker JS from local -->
<script type="text/javascript" src="{{ asset('js/daterangepicker.min.js') }}"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $(document).ajaxStart(function () {
            $('#ajax-spinner').show();
        });

        $(document).ajaxStop(function () {
            $('#ajax-spinner').hide();
        });
    });

    $(document).ready(function () {
        // Hàm khởi tạo ngày mặc định là hôm nay (0h đến 23h59)
        function setDefaultDates() {
            const startDate = moment().startOf('day');
            const endDate = moment().endOf('day');

            $('#dateRangePicker').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: true,
                drops: 'up',
                locale: {
                    format: 'YYYY-MM-DD HH:mm:ss',
                    firstDay: 1,
                    applyLabel: 'Áp dụng',
                    cancelLabel: 'Hủy',
                }
            }, function (start, end) {
                // Set lại giá trị hiển thị trong ô input khi người dùng chọn
                $('#dateRangePicker').val(start.format('YYYY-MM-DD HH:mm:ss') + ' - ' + end.format('YYYY-MM-DD HH:mm:ss'));
            });

            // Gán giá trị ban đầu hiển thị cho input
            $('#dateRangePicker').val(startDate.format('YYYY-MM-DD HH:mm:ss') + ' - ' + endDate.format('YYYY-MM-DD HH:mm:ss'));
        }

        setDefaultDates();
    });

    $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
        var startDate = picker.startDate.format('YYYY-MM-DD HH:mm:ss');
        var endDate = picker.endDate.format('YYYY-MM-DD HH:mm:ss');

        refreshAllCharts(startDate, endDate);
    });
</script>

<script type="text/javascript">
numeral.locale('vi');
var canDashboard = @json(auth()->user()->hasRole('dashboard'));
const is_bieudo_dieutringoaitru = @json(config('organization.is_bieudo_dieutringoaitru'));

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

// Sự kiện thay đổi giá trị trong select box
$("#refreshInterval").change(function () {
    refreshInterval = parseInt($(this).val()); // Cập nhật khoảng thời gian mới
    startAutoRefresh(true); // Restart countdown
});

function fetchTransactionData(startDate, endDate) {
    const hasFinanceRole = @json(auth()->user()->hasRole('thungan-tonghop'));
    // Kiểm tra quyền, nếu không có quyền thì hiển thị thông báo
    if (!hasFinanceRole) {
        $("#chart_transaction_types").text("Không có quyền");
        $("#chart_pay_forms").text("Không có quyền");
        $("#chart_cashiers").text("Không có quyền");
        $("#chart_treatment_types").text("Không có quyền");

        renderNoPermissionChart('chart_transaction_types', 'Loại giao dịch');
        renderNoPermissionChart('chart_pay_forms', 'Hình thức thanh toán');
        renderNoPermissionChart('chart_cashiers', 'Thu ngân');
        renderNoPermissionChart('chart_treatment_types', 'Diện điều trị');
        return;
    }

    $.ajax({
        url: "{{ route('fetch-transaction') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function (data) {
        // Vẽ từng biểu đồ Pie Chart riêng biệt
        renderChart('chart_transaction_types', 'Loại giao dịch', data.transactionTypes);
        renderChart('chart_pay_forms', 'Hình thức thanh toán', data.payForms);
        renderChart('chart_cashiers', 'Thu ngân', data.cashiers);
        renderChart('chart_treatment_types', 'Diện điều trị', data.treatmentTypes);
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Lỗi:", textStatus, errorThrown);
    });
}

// Hàm hiển thị biểu đồ "Không có quyền"
function renderNoPermissionChart(containerId, title) {
    Highcharts.chart(containerId, {
        chart: {
            type: 'pie'
        },
        title: {
            text: `${title} - Không có quyền`,
            style: { fontSize: '16px', fontWeight: 'bold' }
        },
        series: [{
            name: 'Không có quyền',
            colorByPoint: true,
            data: [{
                name: 'Không có quyền',
                y: 1,
                color: '#f5f5f5'
            }]
        }],
        plotOptions: {
            pie: {
                dataLabels: {
                    enabled: true,
                    format: 'Không có quyền xem dữ liệu',
                    style: { fontSize: '14px', fontWeight: 'bold' }
                }
            }
        }
    });
}

// Hàm vẽ biểu đồ Pie Chart cho từng category
function renderChart(containerId, title, data) {
    // Tính tổng tiền cho nhóm hiện tại
    const totalAmount = data.reduce((total, item) => total + item.y, 0);
    const formattedTotal = formatNumber(totalAmount); // Định dạng số tiền với dấu phẩy

    Highcharts.chart(containerId, {
        chart: {
            type: 'column', // bar dọc (column)
            backgroundColor: '#fff'
        },
        title: {
            text: `${title}: ${formattedTotal}`,
            style: { fontSize: '18px', fontWeight: 'bold' }
        },
        xAxis: {
            type: 'category',
            title: { text: null },
            labels: { style: { fontSize: '13px' } }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Số tiền',
                style: { fontSize: '13px' }
            },
            labels: {
                formatter: function() {
                    return formatNumber(this.value);
                },
                style: { fontSize: '13px' }
            }
        },
        tooltip: {
            pointFormat: '<b>{point.y:,.0f}</b>',
            style: { fontSize: '13px' }
        },
        legend: {
            enabled: true // Nếu muốn legend thì bật lên
        },
        plotOptions: {
            column: {
                dataLabels: {
                    enabled: true,
                    formatter: function () {
                        return formatNumber(this.y);
                    },
                    style: { fontSize: '12px' }
                },
                colorByPoint: true,
            }
        },
        series: [{
            name: title,
            data: data, // Không cần formatPieSeriesData nữa, truyền mảng data [{name, y}]
            showInLegend: false
        }]
    });
}

// Hàm định dạng dữ liệu cho biểu đồ Pie Chart
function formatPieSeriesData(data) {
    return data.map(item => ({
        name: item.name,
        y: item.y
    }));
}

// Hàm định dạng số tiền với dấu phẩy phân cách hàng nghìn
function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

function fetchExamAndParraclinical(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-exam-paraclinical') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function (data) {
        Highcharts.chart('chart_exam_paraclinical_time', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Trung bình Thời gian chờ & Thực hiện theo Loại dịch vụ',
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: data.categories,
                title: { text: 'Loại dịch vụ', style: { fontSize: '14px' } },
                labels: { style: { fontSize: '13px' } }
            },
            yAxis: {
                min: 0,
                title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } },
                labels: { style: { fontSize: '13px' } }
            },
            tooltip: {
                shared: true,
                valueSuffix: ' phút',
                style: { fontSize: '13px' }
            },
            legend: {
                itemStyle: { fontSize: '13px' }
            },
            plotOptions: {
                column: {
                    pointPadding: 0.1,
                    groupPadding: 0.2,
                    borderWidth: 0,
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                }
            },
            series: data.series
        });
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Lỗi:", textStatus, errorThrown);
    });
}

function fetchDiagnoticImaging(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-diagnotic-imaging') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function (data) {
        Highcharts.chart('chart_diagnotic_imaging_time', {
            chart: {
                type: 'column'
            },
            title: {
                text: 'Trung bình Thời gian chờ & Thực hiện CĐHA',
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: data.categories,
                title: { text: 'Loại dịch vụ', style: { fontSize: '14px' } },
                labels: { style: { fontSize: '13px' } }
            },
            yAxis: {
                min: 0,
                title: { text: 'Thời gian trung bình (phút)', style: { fontSize: '14px' } },
                labels: { style: { fontSize: '13px' } }
            },
            tooltip: {
                shared: true,
                valueSuffix: ' phút',
                style: { fontSize: '13px' }
            },
            legend: {
                itemStyle: { fontSize: '13px' }
            },
            plotOptions: {
                column: {
                    pointPadding: 0.1,
                    groupPadding: 0.2,
                    borderWidth: 0,
                    dataLabels: {
                        enabled: true,
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                }
            },
            series: data.series
        });
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Lỗi:", textStatus, errorThrown);
    });
}

// Hàm để gọi AJAX và vẽ biểu đồ
function fetchAndRenderChart(serviceId, elementId, title, startDate, endDate) {
    $.ajax({
        url: `{{ route('fetch-service-by-type', '') }}/${serviceId}`,
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(response) {
        if (response && response.chartData.length > 0) {
            Highcharts.chart(elementId, {
                chart: {
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45,
                        beta: 0
                    }
                },
                title: {
                    text: `${title}: ${numeral(response.sum_sl).format('0,0')}`,
                    style: { fontSize: '18px', fontWeight: 'bold' }
                },
                tooltip: {
                    pointFormat: '<b>{point.y} ({point.percentage:.1f}%)</b>',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                plotOptions: {
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        depth: 35,
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}: {point.y} ({point.percentage:.1f}%)',
                            style: { fontSize: '10px' }
                        }
                    }
                },
                series: [{
                    name: 'Số lượng',
                    colorByPoint: true,
                    data: response.chartData
                }]
            });
        } else {
            console.log(`Không có dữ liệu để vẽ biểu đồ ${title}.`);
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log(`Lỗi AJAX (${title}): ${textStatus}: ${errorThrown}`);
    });
}

// Hàm cập nhật tất cả các biểu đồ
function refreshAllCharts(startDate, endDate) {
    if (!startDate || !endDate) {
        const now = moment();
        startDate = now.clone().startOf('day').format('YYYY-MM-DD HH:mm:ss');
        endDate = now.clone().endOf('day').format('YYYY-MM-DD HH:mm:ss');
    }

    if (canDashboard) {
        const chartConfigs = [
            { id: 5, element: 'chart_tdcn', title: 'TDCN' },
            { id: 3, element: 'chart_cdha', title: 'CĐHA' },
            { id: 1, element: 'chart_kham', title: 'Khám' },
            { id: 2, element: 'chart_xetnghiem', title: 'Xét nghiệm' },
            { id: 13, element: 'chart_gpb', title: 'GPB' },
            { id: 8, element: 'chart_ns', title: 'Nội soi' },
            { id: 9, element: 'chart_sa', title: 'Siêu âm' }
        ];

        chartConfigs.forEach(config => {
            fetchAndRenderChart(config.id, config.element, config.title, startDate, endDate);
        });

        // Gọi AJAX cập nhật số liệu cho các biểu đồ khác
        sum_treatment(startDate, endDate);
        sum_newpatient(startDate, endDate);
        sum_chuyenvien(startDate, endDate);
        sum_doanhthu(startDate, endDate);
        sum_outtreatmentgrouptreatmenttype(startDate, endDate);
        sum_phauthuat(startDate, endDate);
        sum_thuthuat(startDate, endDate);
        chart_buongbenh(startDate, endDate);
        chart_noitru(startDate, endDate);
        chart_kham_by_room(startDate, endDate);
        fetchExamAndParraclinical(startDate, endDate);
        fetchDiagnoticImaging(startDate, endDate);
        fetchTransactionData(startDate, endDate);
        if (is_bieudo_dieutringoaitru) {
            chart_dieutringoaitru(startDate, endDate);
            chart_patientInRoomNgoaitru(startDate, endDate);
        }
    }
}

// Hàm bắt đầu countdown và refresh dữ liệu
function startAutoRefresh(firstRun = false) {
    clearInterval(refreshTimer); // Xóa bộ đếm cũ nếu có
    countdown = refreshInterval / 1000; // Reset lại thời gian

    if (firstRun) {
        refreshAllCharts(); // Chạy ngay lần đầu tiên
    }

    updateCountdown(); // Hiển thị giá trị ban đầu

    refreshTimer = setInterval(function () {
        countdown--;
        updateCountdown();

        if (countdown <= 0) {
            // Lấy giá trị từ input dateRangePicker
            const range = $('#dateRangePicker').val();
            let startDate = null;
            let endDate = null;

            if (range && range.includes(' - ')) {
                const parts = range.split(' - ');
                startDate = parts[0];
                endDate = parts[1];
            }

            refreshAllCharts(startDate, endDate); // Gọi lại các hàm update dữ liệu
            countdown = refreshInterval / 1000; // Reset lại bộ đếm
        }
    }, 1000);
}

// Khởi động lần đầu
$(document).ready(function () {
    startAutoRefresh(true);
});

//chart_treatment
function sum_treatment(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-treatment') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function (rtnData) {
        if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {
            // Cập nhật tổng số hồ sơ vào HTML
            $("#sum_treatment").text(numeral(rtnData.sum_sl).format('0,0'));

            // Vẽ Pie Chart 3D bằng Highcharts
            Highcharts.chart('chart_treatment', {
                chart: {
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45, // Góc nghiêng
                        beta: 0
                    }
                },
                title: {
                    text: rtnData.title + ': ' + numeral(rtnData.sum_sl).format('0,0'),
                    style: { fontSize: '18px', fontWeight: 'bold' }
                },
                tooltip: {
                    pointFormat: '<b>{point.y} ({point.percentage:.1f}%)</b>',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                plotOptions: {
                    pie: {
                        innerSize: 0,
                        depth: 45,
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}: {point.percentage:.1f}%',
                            style: { fontSize: '12px' }
                        }
                    }
                },
                series: [{
                    name: 'Hồ sơ',
                    data: rtnData.labels.map((label, i) => ({
                        name: label,
                        y: rtnData.datasets[0].data[i]
                    }))
                }]
            });
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log("Lỗi AJAX: " + textStatus + ': ' + errorThrown);
    });    
}

function sum_newpatient(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-new-patient') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(rtnData) {

        if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {

            // Hiển thị số đã làm tròn với dấu phẩy
            $("#sum_newpatient").text(numeral(rtnData.sum_sl).format('0,0'));
            
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log("Lỗi AJAX: " + textStatus + ': ' + errorThrown);
    });    
}

function sum_chuyenvien(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-chuyen-vien') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(rtnData) {
        if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {
            // Hiển thị số đã làm tròn với dấu phẩy
            $("#sum_chuyenvien").text(numeral(rtnData.sum_sl).format('0,0')); 
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log("Lỗi AJAX: " + textStatus + ': ' + errorThrown);
    });    
}

function sum_phauthuat(startDate, endDate) {
    $.ajax({
        url: `{{ route('fetch-service-by-type', '10') }}`,
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(rtnData) {
        if (rtnData && rtnData.sum_sl > 0) {
            // Hiển thị số đã làm tròn với dấu phẩy
            $("#sum_phauthuat").text(numeral(rtnData.sum_sl).format('0,0')); 
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log(`Lỗi AJAX`);
    });   
}

function sum_outtreatmentgrouptreatmenttype(startDate, endDate) {
    $.ajax({
        url: `{{ route('fetch-out-treatment-group-treatment-type') }}`,
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(rtnData) {
        if (rtnData && rtnData.total > 0) {
            // Hiển thị số đã làm tròn với dấu phẩy
            $("#sum_ravien").text(numeral(rtnData.total).format('0,0'));
            $("#sum_ravien_noitru").text(numeral(rtnData.noitru).format('0,0'));
            $("#sum_ravien_ngoaitru").text(numeral(rtnData.ngoaitru).format('0,0'));
            $("#sum_ravien_kham").text(numeral(rtnData.kham).format('0,0'));
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log(`Lỗi AJAX`);
    });   
}


function sum_thuthuat(startDate, endDate) {
    $.ajax({
        url: `{{ route('fetch-service-by-type', '4') }}`,
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function(rtnData) {
        if (rtnData && rtnData.sum_sl > 0) {
            // Hiển thị số đã làm tròn với dấu phẩy
            $("#sum_thuthuat").text(numeral(rtnData.sum_sl).format('0,0')); 
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.log(`Lỗi AJAX`);
    });   
}

function sum_doanhthu(startDate, endDate) {
    const hasFinanceRole = @json(auth()->user()->hasRole('thungan-tonghop'));
    if (!hasFinanceRole) {
        $("#sum_doanhthu").text("Không có quyền");
        // Hiển thị thông báo không có quyền trong biểu đồ
        Highcharts.chart('chart_doanhthu', {
            chart: {
                type: 'pie'
            },
            title: {
                text: 'Doanh thu',
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            series: [{
                data: [{
                    name: 'Không có quyền',
                    y: 1,
                    color: '#f5f5f5'
                }]
            }],
            plotOptions: {
                pie: {
                    dataLabels: {
                        enabled: true,
                        format: 'Không có quyền xem doanh thu',
                        style: { fontSize: '14px', fontWeight: 'bold' }
                    }
                }
            }
        });
        return;
    }
    
    $.ajax({
        url: "{{ route('fetch-doanh-thu') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    })
    .done(function (rtnData) {
        if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {
            // Cập nhật tổng số doanh thu vào HTML
            let roundedValue = Math.round(rtnData.sum_sl / 1000000);
            $("#sum_doanhthu").text(numeral(roundedValue).format('0,0') + ' Tr');

            // Vẽ biểu đồ Pie 3D bằng Highcharts
            Highcharts.chart('chart_doanhthu', {
                chart: {
                    type: 'pie',
                    options3d: {
                        enabled: true,
                        alpha: 45, // Góc nghiêng
                        beta: 0
                    }
                },
                title: {
                    text: rtnData.title + ': ' + numeral(roundedValue).format('0,0') + ' Tr',
                    style: { fontSize: '18px', fontWeight: 'bold' }
                },
                tooltip: {
                    pointFormat: '<b>{point.y} ({point.percentage:.1f}%)</b>',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                plotOptions: {
                    pie: {
                        innerSize: 0, // Nếu muốn Donut thì đổi > 0
                        depth: 45, // Độ sâu 3D
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}: {point.percentage:.1f}%',
                            style: { fontSize: '12px' }
                        }
                    }
                },
                series: [{
                    name: 'Doanh thu',
                    data: rtnData.labels.map((label, i) => ({
                        name: label,
                        y: rtnData.datasets[0].data[i]
                    }))
                }]
            });
        } else {
            console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
        }
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
        console.log("Lỗi AJAX: " + textStatus + ': ' + errorThrown);
    });    
}    

function chart_buongbenh(startDate, endDate) {
    //Buồng bệnh
    $.ajax({
        url: "{{ route('home.xml_chart') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function(rtnData) {
        $.each(rtnData, function(dataType, data) {
            Highcharts.chart('chart_buongbenh', {
                chart: {
                    type: data.type // 'bar'
                },
                title: {
                    text: data.title,   
                    style: { fontSize: '18px', fontWeight: 'bold' }
                },
                xAxis: {
                    categories: data.labels,
                    title: {
                        text: 'Khoa điều trị',
                        style: { fontSize: '13px', fontWeight: 'bold' }
                    },
                    labels: {
                        rotation: 0, // Xoay nhãn trục X để dễ đọc hơn
                        style: {
                            fontSize: '13px',
                            fontFamily: 'Verdana, sans-serif'
                        }
                    }
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Số lượng bệnh nhân',
                        style: { fontSize: '13px', fontWeight: 'bold' }
                    },
                    labels: {
                        formatter: function() {
                            return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                        }
                    }
                },
                tooltip: {
                    pointFormat: '{series.name}: <b>{point.y}</b>',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                legend: {
                    enabled: false // Tắt legend nếu không cần thiết
                },
                series: [{
                    name: 'Số lượng',
                    data: data.datasets[0].data, // Dữ liệu số lượng
                    colorByPoint: true, // Tự động đổi màu
                    dataLabels: {
                        enabled: true,
                        format: '{point.y}', // Hiển thị số trên cột
                        style: {
                            fontSize: '13px',
                            fontWeight: 'bold'
                        }
                    }
                }]
            });
        });
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus + ': ' + errorThrown);
    });    
}

function chart_kham_by_room(startDate, endDate) {
    $.ajax({
        url: "{{ route('fetch-kham-by-room') }}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function (rtnData) {
        const chartData = rtnData.chartData || [];
        const sum_sl = rtnData.sum_sl || 0;
        const room_count = chartData.length;

        const categories = chartData.map(item => item.room);
        const statuses = ['Chưa thực hiện', 'Đang thực hiện', 'Đã thực hiện'];
        const statusColors = {
            'Chưa thực hiện': '#f45b5b',
            'Đang thực hiện': '#f7a35c',
            'Đã thực hiện': '#90ed7d'
        };

        const series = statuses.map(status => {
            return {
                name: status,
                data: chartData.map(item => item[status] || 0),
                color: statusColors[status] || undefined
            };
        });

        Highcharts.chart('chart_kham_by_room', {
            chart: {
                type: 'column',
                height: 'auto'
            },
            title: {
                text: `
                        <div style="text-align: center">
                            <div><b>Tổng số lượt khám:</b> ${Highcharts.numberFormat(sum_sl, 0, ',', '.')}</div>
                            <div><b>Tổng số phòng thực hiện:</b> ${room_count}</div>
                        </div>
                    `,
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: categories,
                title: {
                    text: 'Phòng thực hiện',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    rotation: -45,
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Số lượng bệnh nhân',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                stackLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        fontSize: '11px'
                    }
                },
                labels: {
                    formatter: function () {
                        return Highcharts.numberFormat(this.value, 0, ',', '.');
                    }
                }
            },
            tooltip: {
                shared: true,
                pointFormat: '<span style="color:{series.color}">●</span> {series.name}: <b>{point.y}</b><br/>',
                style: { fontSize: '13px', fontWeight: 'bold' }
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true,
                        formatter: function () {
                            return this.y > 0 ? this.y : ''; // Chỉ hiển thị khi > 0
                        },
                        style: {
                            fontSize: '11px',
                            fontWeight: 'bold'
                        }
                    }
                }
            },
            legend: {
                enabled: true,
                reversed: false,    
                itemStyle: {
                    fontSize: '13px',
                    fontWeight: 'bold'
                }
            },
            series: series
        });
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.log(textStatus + ': ' + errorThrown);
    });
}
    
function chart_noitru(startDate, endDate) {
    $.ajax({
        url: "{{route('fetch-noi-tru')}}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function(rtnData) {
        let dataObj = Array.isArray(rtnData) ? rtnData[0] : rtnData;

        // Cập nhật tổng số nội trú vào HTML
        $("#sum_noitru").text(numeral(dataObj.sum_sl).format('0,0'));

        Highcharts.chart('chart_noitru', {
            chart: {
                type: dataObj.type // 'bar' hoặc 'column'
            },
            title: {
                text: dataObj.title,
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: dataObj.labels,
                title: {
                    text: 'Khoa điều trị',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    rotation: 0, // Xoay nhãn để dễ đọc
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Số lượng bệnh nhân',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                    }
                }
            },
            tooltip: {
                pointFormat: '<b>{point.y}</b> bệnh nhân',
                style: { fontSize: '13px', fontWeight: 'bold' }
            },
            legend: {
                enabled: false // Ẩn legend nếu không cần thiết
            },
            series: [{
                name: 'Số lượng',
                data: dataObj.datasets[0].data, // Dữ liệu số lượng nội trú
                colorByPoint: true, // Tự động đổi màu
                dataLabels: {
                    enabled: true,
                    format: '{point.y}', // Hiển thị số trên cột
                    style: {
                        fontSize: '13px',
                        fontWeight: 'bold'
                    }
                }
            }]
        });
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus + ': ' + errorThrown);
    });
}

function chart_dieutringoaitru(startDate, endDate) {
    $.ajax({
        url: "{{route('fetch-dieu-tri-ngoai-tru')}}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function(rtnData) {
        let dataObj = Array.isArray(rtnData) ? rtnData[0] : rtnData;

        Highcharts.chart('chart_vaovien_dieutringoaitru', {
            chart: {
                type: dataObj.type // 'bar' hoặc 'column'
            },
            title: {
                text: dataObj.title,
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: dataObj.labels,
                title: {
                    text: 'Khoa điều trị',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    rotation: 0, // Xoay nhãn để dễ đọc
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Số lượng bệnh nhân',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                    }
                }
            },
            tooltip: {
                pointFormat: '<b>{point.y}</b> bệnh nhân',
                style: { fontSize: '13px', fontWeight: 'bold' }
            },
            legend: {
                enabled: false // Ẩn legend nếu không cần thiết
            },
            series: [{
                name: 'Số lượng',
                data: dataObj.datasets[0].data, // Dữ liệu số lượng nội trú
                colorByPoint: true, // Tự động đổi màu
                dataLabels: {
                    enabled: true,
                    format: '{point.y}', // Hiển thị số trên cột
                    style: {
                        fontSize: '13px',
                        fontWeight: 'bold'
                    }
                }
            }]
        });
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus + ': ' + errorThrown);
    });
}

function chart_patientInRoomNgoaitru(startDate, endDate) {
    $.ajax({
        url: "{{route('fetch-patient-in-room-ngoai-tru')}}",
        type: "GET",
        dataType: 'json',
        data: {
            startDate: startDate,
            endDate: endDate
        }
    }).done(function(rtnData) {
        let dataObj = Array.isArray(rtnData) ? rtnData[0] : rtnData;

        Highcharts.chart('chart_buongbenh_dieutringoaitru', {
            chart: {
                type: dataObj.type // 'bar' hoặc 'column'
            },
            title: {
                text: dataObj.title,
                style: { fontSize: '18px', fontWeight: 'bold' }
            },
            xAxis: {
                categories: dataObj.labels,
                title: {
                    text: 'Khoa điều trị',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    rotation: 0, // Xoay nhãn để dễ đọc
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Số lượng bệnh nhân',
                    style: { fontSize: '13px', fontWeight: 'bold' }
                },
                labels: {
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                    }
                }
            },
            tooltip: {
                pointFormat: '<b>{point.y}</b> bệnh nhân',
                style: { fontSize: '13px', fontWeight: 'bold' }
            },
            legend: {
                enabled: false // Ẩn legend nếu không cần thiết
            },
            series: [{
                name: 'Số lượng',
                data: dataObj.datasets[0].data, // Dữ liệu số lượng nội trú
                colorByPoint: true, // Tự động đổi màu
                dataLabels: {
                    enabled: true,
                    format: '{point.y}', // Hiển thị số trên cột
                    style: {
                        fontSize: '13px',
                        fontWeight: 'bold'
                    }
                }
            }]
        });
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.log(textStatus + ': ' + errorThrown);
    });
}

</script>
@endpush