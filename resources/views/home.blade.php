@extends('adminlte::page')

@section('title', __('manager.backend.labels.title'))

@section('content_header')
<h1>
    Dashboard
    <small>Control panel</small>
</h1>
{{ Breadcrumbs::render('dashboard') }}
@stop

@section('content')

<div class="panel panel-default">
    <div class="panel-body">

      <div class="row">

        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-aqua">
            <div class="inner">
              <h3>{{ number_format($sum_doanhthu) }}</h3>
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
              <h3>{{ number_format($sum_treatment) }}</h3>

              <p>Hồ sơ</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-yellow">
            <div class="inner">
              <h3>{{ number_format($sum_newpatient) }}</h3>

              <p>BN mới</p>
            </div>
            <div class="icon">
              <i class="ion ion-person-add"></i>
            </div>
          </div>
        </div>
        <!-- ./col -->
        <div class="col-lg-3 col-xs-6">
          <!-- small box -->
          <div class="small-box bg-red">
            <div class="inner">
              <h3>{{ number_format($sum_noitru) }}</h3>

              <p>Nội trú</p>
            </div>
            <div class="icon">
              <i class="ion ion-pie-graph"></i>
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
                        <canvas id="chart_buongbenh"></canvas>
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
                        <canvas id="chart_noitru"></canvas>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding"><label id="label">Doanh thu</label>
                    <div class="chart tab-pane active" style="position: relative; width: 100%; height: 100%;">
                        <canvas id="chart_doanhthu"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 connectedSortable">
            <div class="nav-tabs-custom text-center"> <!-- Thêm 'text-center' để căn giữa -->
                <div class="tab-content no-padding"><label id="label">Hồ sơ</label>
                    <div class="chart tab-pane active" style="position: relative; width: 100%; height: 100%;">
                        <canvas id="chart_treatment"></canvas>
                    </div>
                </div>
            </div>
        </div>

      </div>

    </div>
</div>

@stop

@push('after-scripts')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
<script src="{{ asset('vendor/numeral/locales.js') }}"></script>

<script type="text/javascript">
    numeral.locale('vi');

    $(document).ready(function() {
        $.ajax({
            url: "{{ route('fetch-treatment') }}",
            type: "GET",
            dataType: 'json',
        })
        .done(function(rtnData) {

            if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {
                var ctx = document.getElementById("chart_treatment").getContext("2d");

                // Hủy biểu đồ cũ nếu có
                if (window.chartTreament instanceof Chart) {
                    window.chartTreament.destroy();
                }

                // Vẽ Pie Chart với labels hiển thị dưới biểu đồ
                window.chartTreament = new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: rtnData.labels, // Đảm bảo labels được truyền vào đây
                        datasets: [{
                            data: rtnData.datasets[0].data,
                            backgroundColor: rtnData.datasets[0].backgroundColor
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Không giữ tỷ lệ cố định
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom', // Chuyển labels xuống dưới biểu đồ
                                labels: {
                                    font: {
                                        size: 12
                                    },
                                    padding: 10
                                }
                            },
                            title: {
                                display: true,
                                text: rtnData.title, // Tiêu đề từ API
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                },
                                padding: {
                                    top: 10,
                                    bottom: 20
                                }
                            }
                        }
                    }
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
            url: "{{ route('fetch-doanh-thu') }}",
            type: "GET",
            dataType: 'json',
        })
        .done(function(rtnData) {

            if (rtnData && rtnData.datasets && rtnData.datasets.length > 0) {
                var ctx = document.getElementById("chart_doanhthu").getContext("2d");

                // Hủy biểu đồ cũ nếu có
                if (window.chartDoanhThu instanceof Chart) {
                    window.chartDoanhThu.destroy();
                }

                // Vẽ Pie Chart với labels hiển thị dưới biểu đồ
                window.chartDoanhThu = new Chart(ctx, {
                    type: "pie",
                    data: {
                        labels: rtnData.labels, // Đảm bảo labels được truyền vào đây
                        datasets: [{
                            data: rtnData.datasets[0].data,
                            backgroundColor: rtnData.datasets[0].backgroundColor
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false, // Không giữ tỷ lệ cố định
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom', // Chuyển labels xuống dưới biểu đồ
                                labels: {
                                    font: {
                                        size: 12
                                    },
                                    padding: 10
                                }
                            },
                            title: {
                                display: true,
                                text: rtnData.title, // Tiêu đề từ API
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                },
                                padding: {
                                    top: 10,
                                    bottom: 20
                                }
                            }
                        }
                    }
                });
            } else {
                console.log("Dữ liệu không hợp lệ hoặc không có dữ liệu!");
            }
        })
        .fail(function(jqXHR, textStatus, errorThrown) {
            console.log("Lỗi AJAX: " + textStatus + ': ' + errorThrown);
        });
    });

    //Buồng bệnh
    $.ajax({
      url: "{{route('home.xml_chart')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_buongbenh").getContext("2d");
          var config = {
            type: data.type,
            data: {
              datasets: $.each(data.datasets, function(dataType, data){
                return data
              }),
              labels: data.labels
            },
            options:  {
              scaleShowValues: true,
              responsive: true,
              title: {
                  display: true,
                  text: data.title
              },
              scales: {
                yAxes: [{
                  ticks: {
                    callback: function (value) {
                        return numeral(value).format('0,0');
                    }
                  }
                }]
              },
              scales: {
                xAxes: [{
                  ticks: {
                    autoSkip: false,
                    callback: function(value) {
                        return value.substr(0, 10);//truncate
                    },
                  }
                }],
                yAxes: [{}]
              },
              tooltips:{
                enabled: true,
                mode: 'label',
                callbacks:{
                  title: function(tooltipItems, data) {
                    var idx = tooltipItems[0].index;
                    return data.labels[idx];//do something with title
                  },
                  label: function(value){
                    return numeral(value.yLabel).format('0,0');
                  }
                }
              }
            }
          };
          var chart = new Chart(ctx, config);
      });
    }).fail(function(jqXHR, textStatus, errorThrown) {
      // If fail
      console.log(textStatus + ': ' + errorThrown);
    });

    //Nội trú
    $.ajax({
      url: "{{route('fetch-noi-tru')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_noitru").getContext("2d");
          var config = {
            type: data.type,
            data: {
              datasets: $.each(data.datasets, function(dataType, data){
                return data
              }),
              labels: data.labels
            },
            options:  {
              scaleShowValues: true,
              responsive: true,
              title: {
                  display: true,
                  text: data.title
              },
              scales: {
                yAxes: [{
                  ticks: {
                    callback: function (value) {
                        return numeral(value).format('0,0');
                    }
                  }
                }]
              },
              scales: {
                xAxes: [{
                  ticks: {
                    autoSkip: false,
                    callback: function(value) {
                        return value.substr(0, 10);//truncate
                    },
                  }
                }],
                yAxes: [{}]
              },
              tooltips:{
                enabled: true,
                mode: 'label',
                callbacks:{
                  title: function(tooltipItems, data) {
                    var idx = tooltipItems[0].index;
                    return data.labels[idx];//do something with title
                  },
                  label: function(value){
                    return numeral(value.yLabel).format('0,0');
                  }
                }
              }
            }
          };
          var chart = new Chart(ctx, config);
      });
    }).fail(function(jqXHR, textStatus, errorThrown) {
      // If fail
      console.log(textStatus + ': ' + errorThrown);
    });
</script>
@endpush