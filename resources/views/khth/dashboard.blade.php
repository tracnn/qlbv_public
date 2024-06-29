<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Trung tâm thông tin</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/bootstrap/dist/css/bootstrap.min.css') }}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/font-awesome/css/font-awesome.min.css') }}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{ asset('vendor/adminlte/vendor/Ionicons/css/ionicons.min.css') }}">

  <link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">

  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body class="hold-transition">
  <div>
    <marquee id="sticky_note"></marquee>
  </div>

  <div class="col">
      <!-- Left col -->
      <div class="col-lg-6 connectedSortable">
          <!-- Custom tabs (Charts with tabs)-->
          <div class="nav-tabs-custom">
              <!-- Tabs within a box -->
              <div class="tab-content no-padding">
                  <!-- Morris chart - Sales -->
                  <div class="chart tab-pane active" style="position: relative;">
                      <canvas id="chart_kham"></canvas>
                  </div>
              </div>
          </div>
      </div>
      <!-- Right col -->
      <div class="col-lg-6 connectedSortable">
          <!-- Custom tabs (Charts with tabs)-->
          <div class="nav-tabs-custom">
              <!-- Tabs within a box -->
              <div class="tab-content no-padding">
                  <!-- Morris chart - Sales -->
                  <div class="chart tab-pane active" style="position: relative;">
                      <canvas id="chart_nhapvien"></canvas>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <div class="col">
    <!-- Left col -->
    <div class="col-lg-6 connectedSortable">
      <!-- Custom tabs (Charts with tabs)-->
      <div class="nav-tabs-custom">
          <!-- Tabs within a box -->
          <div class="tab-content no-padding">
              <!-- Morris chart - Sales -->
              <div class="chart tab-pane active" style="position: relative;">
                  <canvas id="chart_cls"></canvas>
              </div>
          </div>
      </div>
    </div>
    <div class="col-lg-6 connectedSortable">
        <div class="col-lg-6 connectedSortable">
          <!-- Custom tabs (Charts with tabs)-->
          <div class="nav-tabs-custom">
              <!-- Tabs within a box -->
              <div class="tab-content no-padding">
                  <!-- Morris chart - Sales -->
                  <div class="chart tab-pane active" style="position: relative;">
                      <canvas id="chart_xetnghiem"></canvas>
                  </div>
              </div>
          </div>
        </div>
        <!-- Right col -->
        <div class="col-lg-6 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="nav-tabs-custom">
                <!-- Tabs within a box -->
                <div class="tab-content no-padding">
                    <!-- Morris chart - Sales -->
                    <div class="chart tab-pane active" style="position: relative;">
                        <canvas id="chart_pttt"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>

  <script src="{{ asset('vendor/adminlte/vendor/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/adminlte/vendor/bootstrap/dist/js/bootstrap.min.js') }}"></script>
  <script src="{{asset('vendor/print-this/printThis.js')}}"></script>
  <script src="{{ asset('/js/jquery.countdown.js')}}"></script>
  <script src="{{ asset('/js/customize.js')}}"></script>
  <script src="{{asset('vendor/ckeditor/ckeditor.js')}}"></script>
  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
  <script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
  <script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
  <script src="{{ asset('vendor/numeral/locales.js') }}"></script>
  <script type="text/javascript">
    $(document).ready(function() {
      $.ajax({
        url: "{{route('khth.get-sticky-note')}}",
        dataType: 'json',
        type: "GET",
      }).done(function(data) {
        // If successful
        $('#sticky_note').html(data.content);
      }).fail(function(jqXHR, textStatus, errorThrown) {
        // If fail
        console.log(textStatus + ': ' + errorThrown);
      });
      load_data();
      setInterval(load_data, 300000);
    });

  </script>


  <script type="text/javascript">
  function load_data() {
    numeral.locale('vi');
    $.ajax({
      url: "{{route('khth.chart_nhapvien')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_nhapvien").getContext("2d");
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

    $.ajax({
      url: "{{route('khth.chart_kham')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_kham").getContext("2d");
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

    $.ajax({
      url: "{{route('khth.chart_xetnghiem')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_xetnghiem").getContext("2d");
          var config = {
            type: data.type,
            data: {
              datasets: $.each(data.datasets, function(dataType, data){
                return data
              }),
              labels: data.labels
            },
            options:  {
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
              tooltips:{
                callbacks:{
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

    $.ajax({
      url: "{{route('khth.chart_pttt')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_pttt").getContext("2d");
          var config = {
            type: data.type,
            data: {
              datasets: $.each(data.datasets, function(dataType, data){
                return data
              }),
              labels: data.labels
            },
            options:  {
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
              tooltips:{
                callbacks:{
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

    $.ajax({
      url: "{{route('khth.chart_cls')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_cls").getContext("2d");
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
  }
  </script>

  <script src="https://js.pusher.com/4.1/pusher.min.js"></script>
  <script>
    $(document).ready(function(){
      var pusher = new Pusher('32ba995928282d3d2fce', {
          cluster: 'ap1',
          encrypted: true
      });

      var channel = pusher.subscribe('khth-dashboard');

      channel.bind('App\\Events\\DemoPusherEvent', addMessage);

    });

    //function add message
    function addMessage(data) {
      $('#sticky_note').html(data.message);
    }
  </script>

</body>
</html>