<style>
    .warning { background-color: #f7d976; } /* Màu vàng nhạt */
    .info { background-color: #93cbe3; } /* Màu xanh nhạt */
</style>
<div id="hdct" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($service_req_notStarted) && count($service_req_notStarted))
      <table id="service_req" class="table table-bordered table-hover table-striped" width="100%">
          <thead>
              <tr class="info">
                  <th style="text-align:center;">STT</th>
                  <th style="text-align:center;">Số xếp hàng</th>
                  <th style="text-align:center;">Dịch vụ kỹ thuật</th>
                  <th style="text-align:center;">Hướng dẫn</th>
              </tr>
          </thead>
          <tbody>
              @foreach($service_req_notStarted as $key => $value)
                  @switch($value->service_req_stt_code)
                      @case('01')
                          <tr>
                          @break
                      @case('02')
                          <tr class="warning">
                          @break
                      @case('03')
                          <tr class="info">
                          @break
                  @endswitch
                  <td align="center">
                      {{ $key + 1 }}
                  </td>
                  <td align="center">
                      {{ $value->num_order }}
                  </td>
                  <td>
                      {{ $value->tdl_service_name }}
                  </td>
                  <td>
                      {{ $value->address }}
                  </td>
                  </tr>
              @endforeach
          </tbody>
      </table>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>