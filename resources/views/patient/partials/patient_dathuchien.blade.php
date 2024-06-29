<style>
    .table th, .table td {
        vertical-align: middle;
    }
</style>
<div id="hdct_done" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($service_req) && count($service_req))
      <table id="service_req_done" class="table table-striped table-bordered table-hover" width="100%">
          <thead>
              <tr class="info">
                  <th style="text-align:center;">STT</th>
                  <th style="text-align:center;">Hoàn thành</th>
                  <th style="text-align:center;">Dịch vụ kỹ thuật</th>
                  <th style="text-align:center;">Hướng dẫn</th>
              </tr>
          </thead>
          <tbody>
              @foreach($service_req->groupBy('service_req_type_name') as $key_service => $service_items)
                  <tr class="active">
                      <td colspan="4"><strong>{{$key_service}}</strong></td>
                  </tr>
                  @foreach($service_items as $index => $item)
                      <tr class="{{ $item->service_req_stt_code == '01' ? 'warning' : ($item->service_req_stt_code == '03' ? 'success' : '') }}">
                          <td align="center">{{ $index + 1 }}</td>
                          <td align="center">
                              <img src="{{ asset($item->service_req_stt_code == '01' ? 'images/not.png' : ($item->service_req_stt_code == '02' ? 'images/inprogress.png' : 'images/checked.png')) }}" alt="Status Icon" style="height:20px;">
                          </td>
                          <td>{{ $item->tdl_service_name }}</td>
                          <td>{{ $item->address }}</td>
                      </tr>
                  @endforeach
              @endforeach
          </tbody>
      </table>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>