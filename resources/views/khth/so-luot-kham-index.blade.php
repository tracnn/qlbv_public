@extends('adminlte::page')

@section('title', 'Số lượt khám')

@section('content_header')
  <h1>
    KHTH
    <small>Số lượt khám</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>Điều kiện</b>
        </div>

        <form type="GET" action="{{route('khth.so-luot-kham-index')}}">
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

            <div class="col-sm-6">
                <div class="form-group row">
                    <div class="col-sm-12">
                        <div class="form-group row">
                            <div class="col-sm-12 row">
                                <div class="col-sm-3">
                                    <label for="khoa">Phòng khám</label>
                                </div>
                                <div class="col-sm-9 select2">
                                    <select class="form-control exam_room" id="exam_room" name="exam_room[]" multiple="">
                                        @foreach($exam_room as $key_exam => $value_exam)
                                        <option value="{{ $value_exam->room_id }}" @if(in_array($value_exam->room_id, $ParamExamRoom['exam_room'])) selected="" @endif>{{ $value_exam->execute_room_name }}</option>
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

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    Thống kê
                </button>
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
                    <canvas id="chart_soluotkham"></canvas>
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
        url: "{{route('khth.so-luot-kham-get-data')}}",
        type: "GET",
        dataType: 'json',
        data: {
            tu_ngay: $('#tu_ngay').val(),
            den_ngay: $('#den_ngay').val(),
            exam_room: $('#exam_room').val(),
            patient_type: $('#patient_type').val(),
        },
    success: function(rtnData) {
      $('#loading-image').hide();
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_soluotkham").getContext("2d");
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
    $('.exam_room').select2({ 
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