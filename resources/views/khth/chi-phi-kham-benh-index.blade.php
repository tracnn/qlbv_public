@extends('adminlte::page')

@section('title', 'Chi phí khám chữa bệnh')

@section('content_header')
  <h1>
    KHTH
    <small>Chi phí khám chữa bệnh</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Điều kiện lọc</b>
        </div>

        <form type="GET" id="myform" action="{{route('khth.chi-phi-kham-benh-index')}}">
            <div class="col-sm-12">
                <div class="form-group row">
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="ngay_ttoan">Từ</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="tu_ngay" name="tu_ngay" value="{{$ParamNgay['tu_ngay']}}">
                                    </div>
                                </div> 
                            </div>
                           
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="ngay_ttoan">Đến</label>
                                </div>
                                <div class="col-sm-9">
                                    <div class="input-daterange">
                                        <input class="form-control" type="date" id="den_ngay" name="den_ngay" value="{{$ParamNgay['den_ngay']}}">
                                    </div>
                                </div>   
                            </div>
                         
                        </div>

                    </div>
                </div>
            </div>

            <div class="collapse multi-collapse" id="advance_search">
                <div class="col-sm-12">
                    <div class="form-group row">
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <div class="form-group row">
                                        <div class="col-sm-12 row">
                                            <div class="col-sm-3">
                                                <label for="khoa">Diện điều trị</label>
                                            </div>
                                            <div class="col-sm-9 select2">
                                                <select class="form-control treatment_type" id="treatment_type" name="treatment_type[]" multiple="">
                                                    @foreach($treatment_type as $key_treatment_type => $value_treatment_type)
                                                    <option value="{{ $value_treatment_type->id }}" @if(in_array($value_treatment_type->id, $ParamTreatmentType)) selected="" @endif>{{ $value_treatment_type->treatment_type_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>                        
                                        </div>

                                    </div>
                                </div>
                            </div>                    
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <div class="form-group row">
                                        <div class="col-sm-12 row">
                                            <div class="col-sm-3">
                                                <label for="khoa">Đối tượng</label>
                                            </div>
                                            <div class="col-sm-9 select2">
                                                <select class="form-control patient_type" id="patient_type" name="patient_type[]" multiple="">
                                                    @foreach($patient_type as $key_patient_type => $value_patient_type)
                                                    <option value="{{ $value_patient_type->id }}" @if(in_array($value_patient_type->id, $ParamPatientType)) selected="" @endif>{{ $value_patient_type->patient_type_name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>                        
                                        </div>

                                    </div>                    
                                </div>
                            </div>                    
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12">          
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    Thống kê
                </button>
                <a class="btn btn-primary" data-toggle="collapse" href="#advance_search" role="button" aria-expanded="false" aria-controls="advance_search">Nâng cao</a>
            </div>  

        </form>
    </div>
</div>

<div class="row">
    <!-- Left col -->
    <div class="col-lg-12 connectedSortable">
        <!-- Custom tabs (Charts with tabs)-->
        <div class="nav-tabs-custom">
            <!-- Tabs within a box -->
            <img class="center-block" id="loading-image" src="../images/ajax-loader.gif" style="display: none; padding: 10px;" />
            <div class="tab-content no-padding">
                <!-- Morris chart - Sales -->
                <div class="chart tab-pane active" style="position: relative;">
                    <canvas id="chart_chiphi"></canvas>
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
    $('#loading-image').show();
    $.ajax({
        url: "{{route('khth.chi-phi-kham-benh-get-chi-phi')}}",
        type: "GET",
        dataType: 'json',
        data: {
            tu_ngay: $('#tu_ngay').val(),
            den_ngay: $('#den_ngay').val(),
            patient_type: $('#patient_type').val(),
            treatment_type: $('#treatment_type').val(),
        },
    success: function(rtnData) {
      $('#loading-image').hide();
      $.each(rtnData, function(dataType, data) {
          //console.log(data.labels);
          var ctx = document.getElementById("chart_chiphi").getContext("2d");
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
    },
    error: function(rtnData) {
        $('#loading-image').hide();
        alert('error' + rtnData);
    }
  });
</script>

<script>
$(document).ready(function() {
    $('.treatment_type').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });

    $('.patient_type').select2({ 
        width: '100%',
        allowClear: true,
        placeholder: 'Tất cả'
    });    

});
</script>
@endpush