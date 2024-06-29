@extends('adminlte::page')

@section('title', 'Thống kê BN SAR-COV-2')

@section('content_header')
  <h1>
    KHTH
    <small>BN đang điều trị (+) SAR-COV-2 theo khoa</small>
  </h1>

@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
<div class="panel-body table-responsive">
    <div class="form-group">
        <b>Tổng hợp</b>
    </div>
    <table id="result" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
        <thead>
            <tr>
                <th>Thời điểm tổng hợp</th>
                <th>Số kỳ này</th>
                <th>Số kỳ trước</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
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
                    <canvas id="chart_sarcov2"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal form to edit a form tong ket -->
<div id="viewModal" class="modal fade" role="dialog" data-keyboard="false" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <div class="col-sm-12">
                            <table class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Khoa điều trị</th>
                                        <th>Số lượng</th>
                                    </tr>
                                </thead>
                                <tbody id="sarcov2_ct"></tbody>
                            </table>
                        </div>         
                    </div>
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-dismiss="modal" id="close">
                        <span class='glyphicon glyphicon-remove'></span> Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to edit a form -->

@stop
@push('after-scripts')
<script src="{{ asset('vendor/chart/js/Chart.min.js') }}"></script>
<script src="{{ asset('vendor/numeral/numeral.js') }}"></script>
<script src="{{ asset('vendor/numeral/locales.js') }}"></script>
<script type="text/javascript">
    numeral.locale('vi');
    $('#loading-image').show();

    $.ajax({
      url: "{{route('khth.chart_sarcov2')}}",
      type: "GET",
      dataType: 'json',
    })
    .done(function(rtnData) {
        $('#loading-image').hide();
      $.each(rtnData, function(dataType, data) {
          var ctx = document.getElementById("chart_sarcov2").getContext("2d");
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

<script type="text/javascript">
  $(document).ready(function() {
    $('#result').DataTable({
      "processing": true,
      "serverSide": true,
      "stateSave": true,
      "searchDelay": 1000,
      "ajax": {
          url: "{{ route('khth.get-result') }}",
          // data: {
          //     tu_ngay: $('#tu_ngay').val(),
          //     den_ngay: $('#den_ngay').val(),
          //     loai_dvkt: $('#loai_dvkt').val(),
          //     dvkt: $('#dvkt').val(),
          //     department: $('#department').val(),
          //     execute_room: $('#execute_room').val(),
          //     execute_department: $('#execute_department').val(),
          //     icd: $('#icd').val(),
          //     request_room: $('#request_room').val(),
          // }
      },
      "columns": [
          { "data": "ngay_ctu", "name": "ngay_ctu" },
          { "data": "so_cky", "name": "so_cky" },
          { "data": "so_dky", "name": "so_dky" },
          { "data": "action", "name": "action" },
      ],
      "oLanguage": {
        "sUrl": "{{asset('vendor/datatables/lang/vi.json')}}"
      },
  });
})


$(document).on('click', '.view-modal', function() {
  $('.modal-title').html('Chi tiết theo khoa');
  $('#viewModal').modal('show');
  $('#loading_center').show();
  $.ajax({
    type: 'GET',
    url: '{{route("khth.get-sarcov2-ct")}}',
    data: {
        'id': $(this).data('id')
    },
    success: function(data) {
      $('#loading_center').hide();
        switch (data.maKetqua)
        {
            case '500': {
                toastr.error(data.noiDung);
                break;
            }
            case '400': {
                toastr.warning(data.noiDung);
                break;
            }
            default : {
              console.log($(this).data('id'));
                $('#sarcov2_ct').html(data);
            }
        }
    }
  });  
});

</script>
@endpush