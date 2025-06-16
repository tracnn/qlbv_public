<div id="xemcdha" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($sere_serv_cdha) && count($sere_serv_cdha))
      <div class="table">
        <table id="service_cdha" class="table table-bordered table-hover">
          <thead>
            <tr class="info">
              <th style="text-align:center;">STT</th>
              <th style="text-align:center;">Tên dịch vụ</th>
              <th style="text-align:center;">Tác vụ</th>
            </tr>
          </thead>
          <tbody>
            @foreach($sere_serv_cdha as $key => $value)
            <tr>
              <td align="center">
                {{ $key + 1 }}
              </td>
              <td>
                {{ $value->tdl_service_name }}
              </td>
              <td align="center">
                <a href="{{ config('organization.base_pacs_url') }}{{ $value->id }}
                {{ config('organization.pacs_url_suffix') ? config('organization.pacs_url_suffix') . $value->id : '' }}" 
                class="btn btn-info btn-sm" target="_blank" rel="noopener noreferrer">
                  <i class="fa fa-film"></i> Xem
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>