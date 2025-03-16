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

    </div>
</div>

@stop

@push('after-scripts')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
<script src="{{ asset('vendor/numeral/locales.js') }}"></script>
<script type="text/javascript">
    numeral.locale('vi');
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
</script>
@endpush