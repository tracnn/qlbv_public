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

@section('content')
@if(auth()->user()->hasRole('dashboard') || auth()->user()->hasRole('superadministrator'))
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
              <p>Hồ sơ</p>
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

              <p>Hồ sơ mới</p>
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

              <p>Nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-2 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-gray">
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

      <div class="row">
        <div class="col-lg-6 connectedSortable">

            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <div id="chart_buongbenh" style="width:100%; height:400px;"></div>
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
                        <div id="chart_noitru" style="width:100%; height:400px;"></div>
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

    </div>
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

<script type="text/javascript">
    numeral.locale('vi');

$(document).ready(function () {
    $.ajax({
        url: "{{ route('fetch-treatment') }}",
        type: "GET",
        dataType: 'json',
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
                    text: rtnData.title + ': ' + numeral(rtnData.sum_sl).format('0,0')
                },
                plotOptions: {
                    pie: {
                        innerSize: 0,
                        depth: 45,
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Hồ sơ',
                    data: rtnData.labels.map((label, i) => ({
                        name: label,
                        y: rtnData.datasets[0].data[i],
                        color: rtnData.datasets[0].backgroundColor[i]
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
});

    $(document).ready(function() {
        $.ajax({
            url: "{{ route('fetch-new-patient') }}",
            type: "GET",
            dataType: 'json',
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
    });

    $(document).ready(function() {
        $.ajax({
            url: "{{ route('fetch-chuyen-vien') }}",
            type: "GET",
            dataType: 'json',
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
    });
    
    
$(document).ready(function () {
    $.ajax({
        url: "{{ route('fetch-doanh-thu') }}",
        type: "GET",
        dataType: 'json',
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
                    text: rtnData.title + ': ' + numeral(roundedValue).format('0,0') + ' Tr'
                },
                plotOptions: {
                    pie: {
                        innerSize: 0, // Nếu muốn Donut thì đổi > 0
                        depth: 45, // Độ sâu 3D
                        dataLabels: {
                            enabled: true,
                            format: '{point.name}: {point.percentage:.1f}%'
                        }
                    }
                },
                series: [{
                    name: 'Doanh thu',
                    data: rtnData.labels.map((label, i) => ({
                        name: label,
                        y: rtnData.datasets[0].data[i],
                        color: rtnData.datasets[0].backgroundColor[i] // Giữ nguyên màu sắc từ backend
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
});

//Buồng bệnh
$.ajax({
    url: "{{ route('home.xml_chart') }}",
    type: "GET",
    dataType: 'json',
}).done(function(rtnData) {
    $.each(rtnData, function(dataType, data) {
        Highcharts.chart('chart_buongbenh', {
            chart: {
                type: data.type // 'bar'
            },
            title: {
                text: data.title
            },
            xAxis: {
                categories: data.labels,
                title: {
                    text: 'Khoa điều trị'
                },
                labels: {
                    rotation: -45, // Xoay nhãn trục X để dễ đọc hơn
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Số lượng bệnh nhân'
                },
                labels: {
                    formatter: function() {
                        return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                    }
                }
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.y}</b>'
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

$.ajax({
    url: "{{route('fetch-noi-tru')}}",
    type: "GET",
    dataType: 'json',
}).done(function(rtnData) {
    let dataObj = Array.isArray(rtnData) ? rtnData[0] : rtnData;

    // Cập nhật tổng số nội trú vào HTML
    $("#sum_noitru").text(numeral(dataObj.sum_sl).format('0,0'));

    Highcharts.chart('chart_noitru', {
        chart: {
            type: dataObj.type // 'bar' hoặc 'column'
        },
        title: {
            text: dataObj.title
        },
        xAxis: {
            categories: dataObj.labels,
            title: {
                text: 'Khoa điều trị'
            },
            labels: {
                rotation: -45, // Xoay nhãn để dễ đọc
                style: {
                    fontSize: '13px',
                    fontFamily: 'Verdana, sans-serif'
                }
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Số lượng bệnh nhân'
            },
            labels: {
                formatter: function() {
                    return Highcharts.numberFormat(this.value, 0, ',', '.'); // Định dạng số
                }
            }
        },
        tooltip: {
            pointFormat: '<b>{point.y}</b> bệnh nhân'
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
</script>
@endpush