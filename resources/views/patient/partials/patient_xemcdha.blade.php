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
                @if($value->tdl_intruction_time <= 20240511000000)
                  <a href="http://benhviendakhoanongnghiep.vn:88/ris/viewer?name=clinician&study={{ $value->id }}" class="btn btn-info btn-sm" target="_blank">
                    <i class="fa fa-film"></i> Xem
                  </a>
                @else
                  <a href="http://benhviendakhoanongnghiep.vn:82/VrPacs/viewImgsH?pcode={{ $value->tdl_patient_code }}&scode={{ $value->id }}" class="btn btn-info btn-sm" target="_blank">
                    <i class="fa fa-film"></i> Xem
                  </a>
                @endif
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